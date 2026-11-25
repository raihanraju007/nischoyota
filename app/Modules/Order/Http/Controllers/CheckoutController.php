<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Exceptions\OtpException;
use App\Modules\Account\Http\Requests\VerifyCodeRequest;
use App\Modules\Account\Models\User;
use App\Modules\Order\Data\CheckoutData;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Exceptions\CheckoutException;
use App\Modules\Order\Http\Requests\PlaceOrderRequest;
use App\Modules\Order\Services\CheckoutService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Shopping\Http\CurrentCart;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Cash on Delivery checkout (F-023). Session keys: a one-time page token (double submit), a guest's
 * checkout waiting for the phone code, the phones confirmed in this session, the order just placed.
 */
final class CheckoutController extends Controller
{
    private const TOKEN_KEY = 'checkout.token';

    private const PENDING_KEY = 'checkout.pending';

    private const CONFIRMED_PHONES_KEY = 'checkout.confirmed_phones';

    private const PLACED_ORDER_KEY = 'checkout.placed_order';

    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly OrderService $orderService,
    ) {}

    public function show(Request $request, CurrentCart $currentCart): View|RedirectResponse
    {
        $contents = $currentCart->contents();

        if (! $contents->canCheckout()) {
            return to_route('cart')->with('error', $contents->isEmpty() ? __('Your cart is empty.') : __('Please check your cart: some items need your attention.'));
        }

        $user = $request->user();
        $savedAddresses = $this->checkoutService->savedAddressesFor($user);
        $selectedAddressId = $request->old('address_id', $request->old() === [] ? $savedAddresses->firstWhere('is_default', true)?->id : null);
        $upazilaId = $selectedAddressId !== null && $user instanceof User
            ? $this->checkoutService->upazilaOfSavedAddress($user, (int) $selectedAddressId)
            : ($request->old('upazila_id') === null ? null : (int) $request->old('upazila_id'));

        $token = Str::random(40);
        $request->session()->put(self::TOKEN_KEY, $token);

        return view('checkout.index', [
            'lines' => $contents->lines,
            'totals' => $this->checkoutService->totalsFor($contents, $upazilaId, $user, $user?->phone),
            'savedAddresses' => $savedAddresses,
            'selectedAddressId' => $selectedAddressId === null ? null : (int) $selectedAddressId,
            'customer' => $user,
            'paymentMethods' => PaymentMethod::cases(),
            'checkoutToken' => $token,
            ...$this->checkoutService->locationOptions(
                $request->old('division_id') === null ? null : (int) $request->old('division_id'),
                $request->old('district_id') === null ? null : (int) $request->old('district_id'),
            ),
        ]);
    }

    /**
     * A guest whose phone is not confirmed in this session gets a code first (D2).
     */
    public function store(PlaceOrderRequest $request, CurrentCart $currentCart): RedirectResponse
    {
        if ($request->session()->get(self::TOKEN_KEY) !== $request->checkoutToken()) {
            return $this->alreadyHandled($request);
        }

        $data = $request->toData();

        if ($request->user() === null && ! $this->isPhoneConfirmed($request, $data->customerPhone)) {
            try {
                $this->orderService->ensureCanOrder(null, $data->customerPhone);
            } catch (CheckoutException $exception) {
                return to_route('checkout')->with('error', $exception->getMessage())->withInput($this->formInput($data));
            }

            return $this->askForPhoneCode($request, $data);
        }

        return $this->placeOrder($request, $currentCart, $data);
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        return $pending === null
            ? to_route('checkout')
            : view('checkout.verify', ['maskedPhone' => PhoneNumber::mask($pending['customerPhone'])]);
    }

    public function verify(VerifyCodeRequest $request, CurrentCart $currentCart): RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if ($pending === null) {
            return $this->alreadyHandled($request);
        }

        $data = CheckoutData::fromArray($pending);

        try {
            $this->checkoutService->confirmPhone($data->customerPhone, $request->code());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->push(self::CONFIRMED_PHONES_KEY, $data->customerPhone);
        $request->session()->forget(self::PENDING_KEY);

        return $this->placeOrder($request, $currentCart, $data);
    }

    public function resend(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if ($pending === null) {
            return to_route('checkout');
        }

        try {
            $this->checkoutService->sendPhoneCode($pending['customerPhone'], $request->ip());
        } catch (OtpException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('success', __('A new code is on its way.'));
    }

    public function success(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->session()->get(self::PLACED_ORDER_KEY);
        $order = $orderNumber === null ? null : $this->orderService->findByNumber($orderNumber);

        return $order === null ? to_route('home') : view('checkout.success', ['order' => $order]);
    }

    /**
     * Delivery line and total for the chosen upazila (the page's script calls this when the area changes).
     */
    public function deliveryQuote(Request $request, CurrentCart $currentCart): JsonResponse
    {
        $user = $request->user();
        $upazilaId = $request->integer('upazila_id') ?: null;
        $totals = $this->checkoutService->totalsFor($currentCart->contents(), $upazilaId, $user, $user?->phone);

        return response()->json([
            'deliveryLabel' => $totals->delivery === null ? __('Delivery') : __('Delivery (:zone)', ['zone' => __($totals->delivery->zoneName)]),
            'delivery' => match (true) {
                $upazilaId === null => __('Choose your area'),
                $totals->delivery === null => __("We don't deliver here yet"),
                $totals->delivery->isFree => __('FREE'),
                default => Money::format($totals->delivery->charge),
            },
            'total' => Money::format($totals->total()),
            'expectedTotal' => $totals->total(),
        ]);
    }

    private function placeOrder(Request $request, CurrentCart $currentCart, CheckoutData $data): RedirectResponse
    {
        try {
            $order = $this->orderService->place($data, $currentCart->ownerOrFail(), $request->user());
        } catch (CheckoutException $exception) {
            return to_route('checkout')->with('error', $exception->getMessage())->withInput($this->formInput($data));
        }

        $request->session()->forget(self::TOKEN_KEY);
        $request->session()->put(self::PLACED_ORDER_KEY, $order->order_number);

        return to_route('checkout.success');
    }

    private function askForPhoneCode(Request $request, CheckoutData $data): RedirectResponse
    {
        $request->session()->put(self::PENDING_KEY, $data->toArray());

        try {
            $this->checkoutService->sendPhoneCode($data->customerPhone, $request->ip());
        } catch (OtpException $exception) {
            return to_route('checkout.verify')->with('info', $exception->getMessage());
        }

        return to_route('checkout.verify')->with('info', __('We sent a 6-digit code to your phone.'));
    }

    private function isPhoneConfirmed(Request $request, string $phone): bool
    {
        return in_array($phone, $request->session()->get(self::CONFIRMED_PHONES_KEY, []), true);
    }

    /**
     * The same page was submitted twice, or it expired: show the order just placed, else start again.
     */
    private function alreadyHandled(Request $request): RedirectResponse
    {
        return $request->session()->has(self::PLACED_ORDER_KEY)
            ? to_route('checkout.success')
            : to_route('checkout')->with('error', __('This checkout page expired. Please try again.'));
    }

    /**
     * The form fields to refill after a failed attempt (also for a guest coming back from the code step).
     *
     * @return array<string, mixed>
     */
    private function formInput(CheckoutData $data): array
    {
        return [
            'name' => $data->customerName,
            'phone' => $data->customerPhone,
            'email' => $data->customerEmail,
            'address_id' => $data->savedAddressId,
            'division_id' => $data->newAddress?->divisionId,
            'district_id' => $data->newAddress?->districtId,
            'upazila_id' => $data->newAddress?->upazilaId,
            'area' => $data->newAddress?->area,
            'address_line' => $data->newAddress?->addressLine,
            'postal_code' => $data->newAddress?->postalCode,
            'save_address' => $data->shouldSaveAddress,
        ];
    }
}

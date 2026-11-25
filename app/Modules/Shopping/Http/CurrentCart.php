<?php

namespace App\Modules\Shopping\Http;

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Data\CartContents;
use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The visitor's cart: the signed-in user's, or the guest's found by the token kept in the session.
 * Inject it per action, not in a controller constructor: controllers are reused across requests.
 * Contents are loaded once per request (kept on the request) for the header, drawer and page.
 */
final class CurrentCart
{
    public const SESSION_KEY = 'cart.guest_token';

    private const CONTENTS_ATTRIBUTE = 'shopping.cart_contents';

    private const GUEST_TOKEN_LENGTH = 64;

    public function __construct(
        private readonly Request $request,
        private readonly CartService $cartService,
    ) {}

    /**
     * Null for a guest who has not added anything yet.
     */
    public function owner(): ?CartOwner
    {
        $user = $this->request->user();

        if ($user instanceof User) {
            return CartOwner::user($user);
        }

        $guestToken = $this->request->hasSession() ? $this->request->session()->get(self::SESSION_KEY) : null;

        return $guestToken === null ? null : CartOwner::guest($guestToken);
    }

    /**
     * The owner for an add: a guest gets a token on their first add.
     */
    public function ownerForChange(): CartOwner
    {
        $this->forgetContents();
        $owner = $this->owner();

        if ($owner !== null) {
            return $owner;
        }

        $guestToken = Str::random(self::GUEST_TOKEN_LENGTH);
        $this->request->session()->put(self::SESSION_KEY, $guestToken);

        return CartOwner::guest($guestToken);
    }

    /**
     * For changes to existing lines: a visitor without a cart has nothing to change.
     */
    public function ownerOrFail(): CartOwner
    {
        $this->forgetContents();

        return $this->owner() ?? throw new NotFoundHttpException;
    }

    public function contents(): CartContents
    {
        $contents = $this->request->attributes->get(self::CONTENTS_ATTRIBUTE);

        if (! $contents instanceof CartContents) {
            $contents = $this->cartService->contentsFor($this->owner());
            $this->request->attributes->set(self::CONTENTS_ATTRIBUTE, $contents);
        }

        return $contents;
    }

    /**
     * The guest token, removed from the session (used once, when the guest signs in).
     */
    public function pullGuestToken(): ?string
    {
        $this->forgetContents();

        return $this->request->hasSession() ? $this->request->session()->pull(self::SESSION_KEY) : null;
    }

    private function forgetContents(): void
    {
        $this->request->attributes->remove(self::CONTENTS_ATTRIBUTE);
    }
}

<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Exceptions\AttributeInUseException;
use App\Modules\Catalog\Http\Requests\Admin\SaveAttributeValueRequest;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Services\AttributeService;
use Illuminate\Http\RedirectResponse;

final class AttributeValueController extends Controller
{
    public function __construct(private readonly AttributeService $attributeService) {}

    public function store(SaveAttributeValueRequest $request, ProductAttribute $attribute): RedirectResponse
    {
        $value = $this->attributeService->addValue($attribute, $request->toData());

        return to_route('admin.attributes.edit', $attribute)->with('success', __('Value ":value" added.', ['value' => $value->value]));
    }

    public function update(SaveAttributeValueRequest $request, ProductAttribute $attribute, AttributeValue $value): RedirectResponse
    {
        $this->attributeService->updateValue($value, $request->toData());

        return to_route('admin.attributes.edit', $attribute)->with('success', __('Value updated.'));
    }

    public function destroy(ProductAttribute $attribute, AttributeValue $value): RedirectResponse
    {
        try {
            $this->attributeService->deleteValue($value);
        } catch (AttributeInUseException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.attributes.edit', $attribute)->with('success', __('Value ":value" deleted.', ['value' => $value->value]));
    }
}

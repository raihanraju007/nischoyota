<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Exceptions\AttributeInUseException;
use App\Modules\Catalog\Http\Requests\Admin\EditAttributeRequest;
use App\Modules\Catalog\Http\Requests\Admin\SaveAttributeRequest;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Services\AttributeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AttributeController extends Controller
{
    public function __construct(private readonly AttributeService $attributeService) {}

    public function index(): View
    {
        return view('admin.attributes.index', [
            'attributes' => $this->attributeService->listWithValues(),
        ]);
    }

    public function edit(EditAttributeRequest $request, ProductAttribute $attribute): View
    {
        return view('admin.attributes.edit', [
            'attribute' => $this->attributeService->loadOrderedValues($attribute),
            'editingValue' => $this->attributeService->findValueOfAttribute($attribute, $request->valueId()),
        ]);
    }

    public function store(SaveAttributeRequest $request): RedirectResponse
    {
        $attribute = $this->attributeService->create($request->toData());

        return to_route('admin.attributes.edit', $attribute)
            ->with('success', __('Attribute ":name" created. Now add its values.', ['name' => $attribute->name]));
    }

    public function update(SaveAttributeRequest $request, ProductAttribute $attribute): RedirectResponse
    {
        $this->attributeService->update($attribute, $request->toData());

        return to_route('admin.attributes.edit', $attribute)->with('success', __('Attribute updated.'));
    }

    public function destroy(ProductAttribute $attribute): RedirectResponse
    {
        try {
            $this->attributeService->delete($attribute);
        } catch (AttributeInUseException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.attributes.index')->with('success', __('Attribute ":name" deleted.', ['name' => $attribute->name]));
    }
}

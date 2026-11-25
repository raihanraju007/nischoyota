<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\ProductPageService;
use Illuminate\View\View;

final class ProductPageController extends Controller
{
    public function __construct(private readonly ProductPageService $productPageService) {}

    public function show(string $slug): View
    {
        $product = $this->productPageService->findListedProduct($slug) ?? abort(404);

        return view('products.show', [
            'product' => $product,
            'badge' => $this->productPageService->badge($product),
            'descriptionHtml' => $this->productPageService->descriptionHtml($product),
            'optionGroups' => $this->productPageService->optionGroups($product),
            'pickerVariants' => $this->productPageService->pickerVariants($product),
            'delivery' => $this->productPageService->deliverySummary(),
            'relatedCards' => $this->productPageService->relatedCards($product),
        ]);
    }
}

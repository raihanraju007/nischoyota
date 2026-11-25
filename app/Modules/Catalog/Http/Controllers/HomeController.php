<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Services\HomePageService;
use App\Modules\Catalog\Services\ProductListingService;
use Illuminate\View\View;

/**
 * Storefront home page (F-019).
 */
final class HomeController extends Controller
{
    public function __construct(
        private readonly HomePageService $homePageService,
        private readonly ProductListingService $listingService,
    ) {}

    public function index(): View
    {
        return view('home', [
            'home' => $this->homePageService->sections(),
            'dealHoursLeft' => $this->listingService->dealHoursLeft(),
        ]);
    }
}

<?php

use App\Modules\Account\Providers\AccountServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\Content\Providers\ContentServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\Inventory\Providers\InventoryServiceProvider;
use App\Modules\Location\Providers\LocationServiceProvider;
use App\Modules\Order\Providers\OrderServiceProvider;
use App\Modules\Promotion\Providers\PromotionServiceProvider;
use App\Modules\Review\Providers\ReviewServiceProvider;
use App\Modules\Shopping\Providers\ShoppingServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AccountServiceProvider::class,
    CatalogServiceProvider::class,
    InventoryServiceProvider::class,
    LocationServiceProvider::class,
    ContentServiceProvider::class,
    PromotionServiceProvider::class,
    ShoppingServiceProvider::class,
    OrderServiceProvider::class,
    ReviewServiceProvider::class,
    DashboardServiceProvider::class,
];

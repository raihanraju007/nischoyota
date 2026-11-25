<?php

/*
|--------------------------------------------------------------------------
| Shop defaults
|--------------------------------------------------------------------------
|
| Fallback values. From F-010 the admin can change them as store settings
| (ADR-011); these are used when a setting has not been saved yet.
| Money is in paisa (ADR-007, D4).
|
*/

return [

    'free_delivery_threshold' => (int) env('SHOP_FREE_DELIVERY_THRESHOLD', 200000),

    /*
    | Clothing is paused while the store focuses on food and plants. The code and data model stay;
    | set SHOP_CLOTHING_ENABLED=true to seed and promote the clothing range again.
    */
    'clothing_enabled' => (bool) env('SHOP_CLOTHING_ENABLED', false),

    /*
    | Dates the admin types (e.g. coupon start/end days) are Bangladesh dates.
    | They are stored in UTC like every other timestamp.
    */
    'timezone' => 'Asia/Dhaka',

];

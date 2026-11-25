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
    | Dates the admin types (e.g. coupon start/end days) are Bangladesh dates.
    | They are stored in UTC like every other timestamp.
    */
    'timezone' => 'Asia/Dhaka',

];

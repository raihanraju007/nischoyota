<?php

use App\Modules\Shopping\Models\Cart;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Guest carts untouched for 30 days (F-021). Module models are listed: model:prune only scans app/Models.
Schedule::command('model:prune', ['--model' => [Cart::class]])->daily();

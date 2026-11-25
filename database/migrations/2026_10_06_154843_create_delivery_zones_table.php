<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Courier price bands (Inside Dhaka / Dhaka Suburbs / Outside Dhaka). charge is paisa (D4).
     */
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->unsignedBigInteger('charge');
            $table->unsignedTinyInteger('min_days');
            $table->unsignedTinyInteger('max_days');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};

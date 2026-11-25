<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders keep snapshots of the customer, address and amounts (ADR-007, D6). Money is paisa (D4).
     * order_number is set right after insert (ORD- + 10000 + id), so it is nullable.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->index();

            $table->string('customer_name', 100);
            $table->string('customer_phone', 20)->index();
            $table->string('customer_email')->nullable();

            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->string('division_name');
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->string('district_name');
            $table->foreignId('upazila_id')->constrained()->restrictOnDelete();
            $table->string('upazila_name');
            $table->string('area', 100)->nullable();
            $table->string('address_line');
            $table->string('postal_code', 4)->nullable();

            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_zone_name');
            $table->unsignedTinyInteger('delivery_min_days');
            $table->unsignedTinyInteger('delivery_max_days');

            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount_total')->default(0);
            $table->unsignedInteger('delivery_charge');
            $table->unsignedInteger('total');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 30)->nullable();

            $table->string('payment_method', 20);
            $table->string('payment_status', 20);
            $table->timestamp('placed_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

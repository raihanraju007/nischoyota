<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin order management (F-025): courier hand-over, internal note, and when the order ended.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier_name', 50)->nullable()->after('payment_status');
            $table->string('tracking_code', 50)->nullable()->after('courier_name');
            $table->text('admin_note')->nullable()->after('tracking_code');
            $table->timestamp('delivered_at')->nullable()->after('placed_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_name', 'tracking_code', 'admin_note', 'delivered_at', 'cancelled_at']);
        });
    }
};

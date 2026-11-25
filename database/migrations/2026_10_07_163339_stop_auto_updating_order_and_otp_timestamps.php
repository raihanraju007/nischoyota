<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * With explicit_defaults_for_timestamp off (MariaDB / older MySQL defaults), the first NOT NULL
     * timestamp column gets ON UPDATE CURRENT_TIMESTAMP, so every update rewrote orders.placed_at and
     * otp_codes.expires_at. An explicit default removes the ON UPDATE clause.
     * placed_at is written together with created_at when an order is placed, so rewritten values are restored from it.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('placed_at')->useCurrent()->change();
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->timestamp('expires_at')->useCurrent()->change();
        });

        DB::table('orders')->whereColumn('placed_at', '!=', 'created_at')->update(['placed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Nothing to undo: the columns keep the same type and values.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How a product is sold (piece, kg, gram, litre, ml, pack, …) and the amount per unit, e.g. 500 + gram.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sell_unit', 20)->default('piece')->after('specifications');
            $table->decimal('unit_quantity', 10, 2)->nullable()->after('sell_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sell_unit', 'unit_quantity']);
        });
    }
};

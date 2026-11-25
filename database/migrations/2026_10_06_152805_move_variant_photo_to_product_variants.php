<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A variant points to its photo, so one photo can serve several variants (White/48" and White/56").
     * Replaces product_images.product_variant_id, which allowed only one variant per photo.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('product_image_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_image_id');
        });
    }
};

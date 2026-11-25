<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bangla copies of the customer-facing text (ADR-014). Empty means "show the English text".
     * Existing starter-catalog rows and system pages are filled from database/data/catalog_bn.php.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_bn')->nullable()->after('name');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('name_bn')->nullable()->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name_bn')->nullable()->after('name');
            $table->string('short_description_bn', 500)->nullable()->after('short_description');
            $table->longText('description_bn')->nullable()->after('description');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_bn', 120)->nullable()->after('title');
            $table->string('subtitle_bn')->nullable()->after('subtitle');
            $table->longText('body_bn')->nullable()->after('body');
        });

        $this->fillStarterContent();
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('name_bn'));
        Schema::table('brands', fn (Blueprint $table) => $table->dropColumn('name_bn'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['name_bn', 'short_description_bn', 'description_bn']));
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn(['title_bn', 'subtitle_bn', 'body_bn']));
    }

    private function fillStarterContent(): void
    {
        $content = require database_path('data/catalog_bn.php');

        foreach ($content['categories'] as $slug => $name) {
            DB::table('categories')->where('slug', $slug)->update(['name_bn' => $name]);
        }

        foreach ($content['brands'] as $slug => $name) {
            DB::table('brands')->where('slug', $slug)->update(['name_bn' => $name]);
        }

        foreach ($content['products'] as $slug => [$name, $shortDescription]) {
            $currentName = (string) DB::table('products')->where('slug', $slug)->value('name');

            DB::table('products')->where('slug', $slug)->update([
                // Seeded names used to carry the Bangla name in brackets; it now has its own column.
                'name' => trim((string) preg_replace('/\s*\(.*\)\s*/u', ' ', $currentName)),
                'name_bn' => $name,
                'short_description_bn' => $shortDescription,
                'description_bn' => $shortDescription."\n\n".$content['product_description_suffix'],
            ]);
        }

        foreach ($content['pages'] as $slug => [$title, $subtitle]) {
            $bodyPath = database_path("data/pages/bn/{$slug}.md");

            DB::table('pages')->where('slug', $slug)->where('is_system', true)->update([
                'title_bn' => $title,
                'subtitle_bn' => $subtitle,
                'body_bn' => is_file($bodyPath) ? file_get_contents($bodyPath) : null,
            ]);
        }
    }
};

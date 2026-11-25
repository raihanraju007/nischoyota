<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phone becomes the main login ID and email is optional (ADR-007, D1); role and status per D8.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 11)->unique()->after('name');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('email')->nullable()->change();
            $table->string('role')->default('customer')->index()->after('password');
            $table->string('status')->default('active')->after('role');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_verified_at', 'role', 'status', 'last_login_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};

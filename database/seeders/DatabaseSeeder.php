<?php

namespace Database\Seeders;

use App\Modules\Account\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedLocalAdmin();

        $this->call([DeliveryZoneSeeder::class, LocationSeeder::class]);
        $this->call([CategorySeeder::class, BrandSeeder::class, AttributeSeeder::class, ProductSeeder::class]);
        $this->call([CouponSeeder::class, PageSeeder::class]);
    }

    /**
     * Local development only. Production admins are created with `php artisan app:create-admin`.
     */
    private function seedLocalAdmin(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        User::factory()->admin()->create([
            'name' => 'Admin User',
            'phone' => '01700000000',
            'email' => 'admin@nischoyota.test',
        ]);
    }
}

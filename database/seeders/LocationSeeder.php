<?php

namespace Database\Seeders;

use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use App\Modules\Location\Models\Upazila;
use Illuminate\Database\Seeder;

/**
 * Divisions, districts and upazilas from database/data/bangladesh (see README there), plus the
 * Dhaka Metropolitan thanas, which the official upazila list does not contain. Needs DeliveryZoneSeeder first.
 */
class LocationSeeder extends Seeder
{
    private const DATA_PATH = 'data/bangladesh';

    private const SUBURB_DISTRICTS = ['Dhaka', 'Gazipur', 'Narayanganj'];

    /**
     * Dhaka Metropolitan Police thanas (English => Bangla). Bangla spellings to be reviewed (F-009 §13).
     *
     * @var array<string, string>
     */
    private const DHAKA_CITY_THANAS = [
        'Adabor' => 'আদাবর', 'Badda' => 'বাড্ডা', 'Banani' => 'বনানী', 'Bangshal' => 'বংশাল',
        'Bhashantek' => 'ভাষানটেক', 'Bhatara' => 'ভাটারা', 'Bimanbandar' => 'বিমানবন্দর', 'Cantonment' => 'ক্যান্টনমেন্ট',
        'Chawkbazar' => 'চকবাজার', 'Dakshinkhan' => 'দক্ষিণখান', 'Darus Salam' => 'দারুস সালাম', 'Demra' => 'ডেমরা',
        'Dhanmondi' => 'ধানমন্ডি', 'Gendaria' => 'গেন্ডারিয়া', 'Gulshan' => 'গুলশান', 'Hatirjheel' => 'হাতিরঝিল',
        'Hazaribagh' => 'হাজারীবাগ', 'Jatrabari' => 'যাত্রাবাড়ী', 'Kadamtali' => 'কদমতলী', 'Kafrul' => 'কাফরুল',
        'Kalabagan' => 'কলাবাগান', 'Kamrangirchar' => 'কামরাঙ্গীরচর', 'Khilgaon' => 'খিলগাঁও', 'Khilkhet' => 'খিলক্ষেত',
        'Kotwali' => 'কোতোয়ালী', 'Lalbagh' => 'লালবাগ', 'Mirpur' => 'মিরপুর', 'Mohammadpur' => 'মোহাম্মদপুর',
        'Motijheel' => 'মতিঝিল', 'Mugda' => 'মুগদা', 'New Market' => 'নিউ মার্কেট', 'Pallabi' => 'পল্লবী',
        'Paltan' => 'পল্টন', 'Ramna' => 'রমনা', 'Rampura' => 'রামপুরা', 'Rupnagar' => 'রূপনগর',
        'Sabujbagh' => 'সবুজবাগ', 'Shah Ali' => 'শাহ আলী', 'Shahbagh' => 'শাহবাগ', 'Shahjahanpur' => 'শাহজাহানপুর',
        'Sher-e-Bangla Nagar' => 'শেরেবাংলা নগর', 'Shyampur' => 'শ্যামপুর', 'Sutrapur' => 'সূত্রাপুর', 'Tejgaon' => 'তেজগাঁও',
        'Tejgaon Industrial Area' => 'তেজগাঁও শিল্পাঞ্চল', 'Turag' => 'তুরাগ', 'Uttara East' => 'উত্তরা পূর্ব',
        'Uttara West' => 'উত্তরা পশ্চিম', 'Uttarkhan' => 'উত্তরখান', 'Wari' => 'ওয়ারী',
    ];

    public function run(): void
    {
        $zones = DeliveryZone::query()->pluck('id', 'name');

        foreach ($this->readJson('divisions') as $division) {
            Division::query()->create($division);
        }

        foreach ($this->readJson('districts') as $district) {
            $zoneName = in_array($district['name'], self::SUBURB_DISTRICTS, true)
                ? DeliveryZoneSeeder::DHAKA_SUBURBS
                : DeliveryZoneSeeder::OUTSIDE_DHAKA;

            District::query()->create([...$district, 'delivery_zone_id' => $zones[$zoneName]]);
        }

        foreach (array_chunk($this->readJson('upazilas'), 200) as $chunk) {
            Upazila::query()->insert($chunk);
        }

        $this->seedDhakaCityThanas($zones[DeliveryZoneSeeder::INSIDE_DHAKA]);
    }

    private function seedDhakaCityThanas(int $insideDhakaZoneId): void
    {
        $dhakaDistrictId = District::query()->where('name', 'Dhaka')->value('id');

        foreach (self::DHAKA_CITY_THANAS as $name => $nameBn) {
            Upazila::query()->create([
                'district_id' => $dhakaDistrictId,
                'name' => $name,
                'name_bn' => $nameBn,
                'delivery_zone_id' => $insideDhakaZoneId,
            ]);
        }
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function readJson(string $file): array
    {
        return json_decode(file_get_contents(database_path(self::DATA_PATH."/{$file}.json")), true, flags: JSON_THROW_ON_ERROR);
    }
}

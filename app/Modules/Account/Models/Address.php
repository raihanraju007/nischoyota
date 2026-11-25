<?php

namespace App\Modules\Account\Models;

use App\Modules\Account\Enums\AddressLabel;
use App\Modules\Account\Policies\AddressPolicy;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use App\Modules\Location\Models\Upazila;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved delivery address. The Location relations are read-only, for showing place names.
 */
#[Fillable(['label', 'recipient_name', 'phone', 'division_id', 'district_id', 'upazila_id', 'area', 'address_line', 'postal_code'])]
#[UseFactory(AddressFactory::class)]
#[UsePolicy(AddressPolicy::class)]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'label' => AddressLabel::class,
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * @return BelongsTo<Upazila, $this>
     */
    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }

    /**
     * Lines for display, e.g. ["House 12, Road 5, Dhanmondi", "Dhanmondi, Dhaka 1205"]. Needs upazila and district loaded.
     *
     * @return list<string>
     */
    public function displayLines(): array
    {
        $street = $this->area ? "{$this->address_line}, {$this->area}" : $this->address_line;
        $place = trim("{$this->upazila->name}, {$this->district->name} {$this->postal_code}");

        return [$street, $place];
    }
}

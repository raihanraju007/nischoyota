<?php

namespace App\Modules\Location\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * charge is paisa (ADR-007, D4).
 */
#[Fillable(['name', 'charge', 'min_days', 'max_days', 'is_active', 'sort_order'])]
#[UseFactory(DeliveryZoneFactory::class)]
class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'charge' => 'integer',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<District, $this>
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * @return HasMany<Upazila, $this>
     */
    public function upazilas(): HasMany
    {
        return $this->hasMany(Upazila::class);
    }

    /**
     * "1–2 days" / "3 days".
     */
    public function deliveryTimeLabel(): string
    {
        return $this->min_days === $this->max_days
            ? trans_choice(':count day|:count days', $this->min_days)
            : __(':min–:max days', ['min' => $this->min_days, 'max' => $this->max_days]);
    }

    /**
     * @param  Builder<DeliveryZone>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}

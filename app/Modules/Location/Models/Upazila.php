<?php

namespace App\Modules\Location\Models;

use Database\Factories\UpazilaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An upazila or a Dhaka city thana. delivery_zone_id, when set, overrides the district's zone.
 */
#[Fillable(['id', 'district_id', 'name', 'name_bn', 'delivery_zone_id'])]
#[UseFactory(UpazilaFactory::class)]
#[WithoutTimestamps]
class Upazila extends Model
{
    /** @use HasFactory<UpazilaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * @return BelongsTo<DeliveryZone, $this>
     */
    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }
}

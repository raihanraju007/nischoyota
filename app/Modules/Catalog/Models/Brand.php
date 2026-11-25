<?php

namespace App\Modules\Catalog\Models;

use App\Support\Concerns\TranslatesAttributes;
use App\Support\PublicImageStorage;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'name_bn', 'slug', 'logo', 'is_active'])]
#[UseFactory(BrandFactory::class)]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    use TranslatesAttributes;

    /**
     * @var list<string>
     */
    protected array $translatedAttributes = ['name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function logoUrl(): ?string
    {
        return PublicImageStorage::url($this->logo);
    }
}

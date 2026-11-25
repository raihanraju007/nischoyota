<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Enums\SellUnit;
use App\Support\Concerns\TranslatesAttributes;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Price, SKU and stock live on the variants (ADR-007, D5). min_price and max_discount_percent are
 * cached from the variants by ProductService.
 */
#[Fillable([
    'category_id', 'brand_id', 'name', 'name_bn', 'slug', 'short_description', 'short_description_bn', 'description', 'description_bn', 'specifications',
    'sell_unit', 'unit_quantity', 'status', 'is_featured', 'allow_preorder', 'min_price', 'max_discount_percent',
    'meta_title', 'meta_description', 'published_at',
])]
#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use TranslatesAttributes;

    /**
     * @var list<string>
     */
    protected array $translatedAttributes = ['name', 'short_description', 'description'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sell_unit' => 'piece',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'sell_unit' => SellUnit::class,
            'unit_quantity' => 'float',
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'allow_preorder' => 'boolean',
            'min_price' => 'integer',
            'max_discount_percent' => 'integer',
            'rating_average' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    /**
     * What one unit of the price buys: "500 g", "1 L", "pc".
     */
    public function unitLabel(): string
    {
        return ($this->sell_unit ?? SellUnit::Piece)->describe($this->unit_quantity);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * A product has options when its variants are made of attribute values (Color, Size…).
     */
    /**
     * Total stock of active variants; needs withSum(... as active_stock) from the admin list query.
     */
    public function activeStock(): int
    {
        return (int) $this->getAttribute('active_stock');
    }

    /**
     * Shown on the storefront: active, in an active category. Needs category loaded.
     */
    public function isListed(): bool
    {
        return $this->status === ProductStatus::Active && $this->category->is_active;
    }

    public function hasOptions(): bool
    {
        return $this->variants()->has('attributeValues')->exists();
    }

    /**
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * The lowest-priced active variant: its price and old price are shown on product cards.
     *
     * @return HasOne<ProductVariant, $this>
     */
    public function cheapestVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->ofMany(
            ['price' => 'min', 'id' => 'min'],
            fn ($query) => $query->where('is_active', true),
        );
    }

    /**
     * Ordered by sort_order: the first image is the cover.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function coverImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }
}

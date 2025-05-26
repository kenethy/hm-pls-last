<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SparePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'category_id',
        'brand',
        'part_number',
        'price',
        'original_price',
        'stock_quantity',
        'minimum_stock',
        'images',
        'featured_image',
        'specifications',
        'compatibility',
        'condition',
        'status',
        'is_featured',
        'is_best_seller',
        'is_original',
        'order',
        'warranty_period',
        'installation_notes',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'marketplace_links',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'minimum_stock' => 'integer',
        'images' => 'array',
        'specifications' => 'array',
        'compatibility' => 'array',
        'is_featured' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_original' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sparePart) {
            if (empty($sparePart->slug)) {
                $sparePart->slug = Str::slug($sparePart->name);
            }
        });

        static::updating(function ($sparePart) {
            if ($sparePart->isDirty('name') && empty($sparePart->slug)) {
                $sparePart->slug = Str::slug($sparePart->name);
            }
        });
    }

    /**
     * Get the category that owns the spare part.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SparePartCategory::class, 'category_id');
    }

    /**
     * Scope a query to only include active spare parts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured spare parts.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include best seller spare parts.
     */
    public function scopeBestSeller($query)
    {
        return $query->where('is_best_seller', true);
    }

    /**
     * Scope a query to only include original spare parts.
     */
    public function scopeOriginal($query)
    {
        return $query->where('is_original', true);
    }

    /**
     * Scope a query to only include in-stock spare parts.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope a query to order by the order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Check if the spare part is in stock.
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Check if the spare part is low stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->minimum_stock && $this->stock_quantity > 0;
    }

    /**
     * Get the discount percentage if original price is set.
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if ($this->original_price && $this->original_price > $this->price) {
            return round((($this->original_price - $this->price) / $this->original_price) * 100);
        }
        return null;
    }

    /**
     * Get the formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get the formatted original price.
     */
    public function getFormattedOriginalPriceAttribute(): ?string
    {
        if ($this->original_price) {
            return 'Rp ' . number_format($this->original_price, 0, ',', '.');
        }
        return null;
    }

    /**
     * Get the main image URL.
     */
    public function getMainImageAttribute(): ?string
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }

        if ($this->images && count($this->images) > 0) {
            return asset('storage/' . $this->images[0]);
        }

        return asset('images/sparepart/sparepart.png'); // Default image
    }

    /**
     * Get all image URLs.
     */
    public function getImageUrlsAttribute(): array
    {
        if (!$this->images) {
            return [asset('images/sparepart/sparepart.png')];
        }

        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->images);
    }
}

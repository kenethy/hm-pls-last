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
        'marketplace_links' => 'array',
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

    /**
     * Get available marketplace links.
     */
    public function getAvailableMarketplacesAttribute(): array
    {
        if (!$this->marketplace_links) {
            return [];
        }

        return array_filter($this->marketplace_links, function ($link) {
            return !empty($link['url']);
        });
    }

    /**
     * Check if product has marketplace links.
     */
    public function getHasMarketplaceLinksAttribute(): bool
    {
        return count($this->available_marketplaces) > 0;
    }

    /**
     * Get marketplace link by platform.
     */
    public function getMarketplaceLink(string $platform): ?string
    {
        if (!$this->marketplace_links) {
            return null;
        }

        foreach ($this->marketplace_links as $link) {
            if (isset($link['platform']) && $link['platform'] === $platform && !empty($link['url'])) {
                return $link['url'];
            }
        }

        return null;
    }

    /**
     * Get clean description without HTML tags.
     */
    public function getCleanDescriptionAttribute(): string
    {
        if (!$this->description) {
            return '';
        }

        // Remove HTML tags but keep basic formatting
        $cleanDescription = strip_tags($this->description, '<br><strong><b><em><i><u>');

        // Convert HTML entities to normal characters
        $cleanDescription = html_entity_decode($cleanDescription, ENT_QUOTES, 'UTF-8');

        // Replace multiple <br> tags with double line breaks
        $cleanDescription = preg_replace('/<br\s*\/?>\s*<br\s*\/?>/i', "\n\n", $cleanDescription);

        // Replace single <br> tags with single line breaks
        $cleanDescription = preg_replace('/<br\s*\/?>/i', "\n", $cleanDescription);

        // Clean up extra whitespace
        $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription);

        // Clean up multiple line breaks
        $cleanDescription = preg_replace('/\n\s*\n/', "\n\n", $cleanDescription);

        return trim($cleanDescription);
    }

    /**
     * Get formatted description for display.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        $cleanDescription = $this->clean_description;

        // Convert line breaks to <br> tags for HTML display
        return nl2br(e($cleanDescription));
    }

    /**
     * Get stock status text.
     */
    public function getStockStatusTextAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'Stok Habis';
        } elseif ($this->is_low_stock) {
            return 'Stok Terbatas';
        } else {
            return 'Tersedia';
        }
    }

    /**
     * Get stock status color class.
     */
    public function getStockStatusColorAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'text-red-600';
        } elseif ($this->is_low_stock) {
            return 'text-yellow-600';
        } else {
            return 'text-green-600';
        }
    }

    /**
     * Get main image URL with fallback.
     */
    public function getMainImageUrlAttribute(): string
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
     * Get marketplace platforms configuration.
     */
    public static function getMarketplacePlatforms(): array
    {
        return [
            'shopee' => [
                'name' => 'Shopee',
                'icon' => 'shopee-icon.svg',
                'color' => '#ee4d2d',
                'placeholder' => 'https://shopee.co.id/product/...',
            ],
            'tokopedia' => [
                'name' => 'Tokopedia',
                'icon' => 'tokopedia-icon.svg',
                'color' => '#42b549',
                'placeholder' => 'https://www.tokopedia.com/...',
            ],
            'lazada' => [
                'name' => 'Lazada',
                'icon' => 'lazada-icon.svg',
                'color' => '#0f146d',
                'placeholder' => 'https://www.lazada.co.id/products/...',
            ],
            'bukalapak' => [
                'name' => 'Bukalapak',
                'icon' => 'bukalapak-icon.svg',
                'color' => '#e31e24',
                'placeholder' => 'https://www.bukalapak.com/p/...',
            ],
        ];
    }
}

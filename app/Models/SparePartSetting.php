<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SparePartSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get a setting value by key with caching.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("spare_part_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->where('is_active', true)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, string $description = null, string $type = 'text'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'type' => $type,
                'is_active' => true,
            ]
        );

        // Clear cache
        Cache::forget("spare_part_setting_{$key}");

        return $setting;
    }

    /**
     * Get all pricing notification settings.
     */
    public static function getPricingNotificationSettings(): array
    {
        return [
            'enabled' => (bool) static::get('pricing_notification_enabled', true),
            'title' => static::get('pricing_notification_title', 'Harga Toko Lebih Murah!'),
            'message' => static::get('pricing_notification_message', 'Dapatkan harga sparepart yang lebih murah dengan berbelanja langsung di toko kami!'),
            'cta_text' => static::get('pricing_notification_cta_text', 'Hubungi WhatsApp untuk Harga Terbaik'),
            'whatsapp_number' => static::get('pricing_notification_whatsapp_number', '6282135202581'),
            'display_type' => static::get('pricing_notification_display_type', 'banner'),
        ];
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("spare_part_setting_{$key}");
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("spare_part_setting_{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget("spare_part_setting_{$setting->key}");
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WhatsAppLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'phone',
        'message',
        'response_data',
        'status',
        'error_message',
        'processing_time_ms',
        'qr_uuid',
        'expires_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'expires_at' => 'datetime',
    ];

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeQrGenerated($query)
    {
        return $query->where('type', 'qr_generated');
    }

    public function scopeMessageSent($query)
    {
        return $query->where('type', 'message_sent');
    }

    public function scopeStatusCheck($query)
    {
        return $query->where('type', 'status_check');
    }

    // Accessors
    protected function processingTimeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->processing_time_ms ? $this->processing_time_ms . 'ms' : 'N/A',
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at ? $this->expires_at->isPast() : false,
        );
    }

    // Static methods for logging
    public static function logQrGeneration(array $data, string $status = 'success', ?string $error = null)
    {
        return self::create([
            'type' => 'qr_generated',
            'response_data' => $data,
            'status' => $status,
            'error_message' => $error,
            'processing_time_ms' => $data['total_time_ms'] ?? null,
            'qr_uuid' => $data['qr_link'] ? basename($data['qr_link'], '.png') : null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    public static function logMessageSent(string $phone, string $message, array $response, string $status = 'success', ?string $error = null)
    {
        return self::create([
            'type' => 'message_sent',
            'phone' => $phone,
            'message' => $message,
            'response_data' => $response,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    public static function logStatusCheck(array $response, string $status = 'success', ?string $error = null)
    {
        return self::create([
            'type' => 'status_check',
            'response_data' => $response,
            'status' => $status,
            'error_message' => $error,
        ]);
    }
}

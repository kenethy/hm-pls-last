<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FollowUpTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'message',
        'description',
        'trigger_event',
        'is_active',
        'usage_count',
        'last_used_at',
        // WhatsApp specific fields
        'whatsapp_enabled',
        'include_attachments',
        'whatsapp_message_type',
        'attachment_path',
        'whatsapp_caption',
        'available_variables',
        'auto_send_on_completion',
        'delay_minutes',
        'whatsapp_sent_count',
        'last_whatsapp_sent_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'whatsapp_enabled' => 'boolean',
        'include_attachments' => 'boolean',
        'available_variables' => 'array',
        'auto_send_on_completion' => 'boolean',
        'delay_minutes' => 'integer',
        'whatsapp_sent_count' => 'integer',
        'last_whatsapp_sent_at' => 'datetime',
    ];

    /**
     * Get the WhatsApp messages that used this template.
     */
    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for WhatsApp enabled templates.
     */
    public function scopeWhatsAppEnabled($query)
    {
        return $query->where('whatsapp_enabled', true);
    }

    /**
     * Scope for auto-send templates.
     */
    public function scopeAutoSend($query)
    {
        return $query->where('auto_send_on_completion', true);
    }

    /**
     * Scope for specific trigger event.
     */
    public function scopeForTrigger($query, string $trigger)
    {
        return $query->where('trigger_event', $trigger);
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Increment WhatsApp usage count.
     */
    public function incrementWhatsAppUsage(): void
    {
        $this->increment('whatsapp_sent_count');
        $this->update(['last_whatsapp_sent_at' => now()]);
    }

    /**
     * Get trigger event display.
     */
    public function getTriggerEventDisplay(): string
    {
        return match ($this->trigger_event) {
            'service_completion' => 'Selesai Servis',
            'booking_confirmation' => 'Konfirmasi Booking',
            'payment_reminder' => 'Pengingat Pembayaran',
            'custom' => 'Custom',
            default => 'Unknown',
        };
    }

    /**
     * Get message type display.
     */
    public function getWhatsAppMessageTypeDisplay(): string
    {
        return match ($this->whatsapp_message_type) {
            'text' => 'Teks',
            'image' => 'Gambar',
            'file' => 'File',
            'contact' => 'Kontak',
            'link' => 'Link',
            default => 'Teks',
        };
    }

    /**
     * Get available template variables.
     */
    public static function getAvailableVariables(): array
    {
        return [
            '{customer_name}' => 'Nama Customer',
            '{service_type}' => 'Jenis Servis',
            '{vehicle_info}' => 'Informasi Kendaraan',
            '{completion_date}' => 'Tanggal Selesai',
            '{total_cost}' => 'Total Biaya',
            '{workshop_name}' => 'Nama Bengkel',
            '{workshop_phone}' => 'Telepon Bengkel',
            '{workshop_address}' => 'Alamat Bengkel',
            '{invoice_number}' => 'Nomor Invoice',
            '{mechanic_name}' => 'Nama Montir',
        ];
    }

    /**
     * Replace template variables with actual values.
     */
    public function replaceVariables(Service $service): string
    {
        $variables = [
            '{customer_name}' => $service->customer->name ?? 'Customer',
            '{service_type}' => $service->service_type ?? 'Service',
            '{vehicle_info}' => $service->vehicle_info ?? 'Vehicle',
            '{completion_date}' => $service->updated_at->format('d/m/Y H:i'),
            '{total_cost}' => 'Rp ' . number_format($service->total_cost ?? 0, 0, ',', '.'),
            '{workshop_name}' => 'Hartono Motor',
            '{workshop_phone}' => config('app.workshop_phone', ''),
            '{workshop_address}' => config('app.workshop_address', ''),
            '{invoice_number}' => $service->invoice_number ?? '',
            '{mechanic_name}' => $service->mechanics->pluck('name')->join(', ') ?: 'Tim Montir',
        ];

        return str_replace(array_keys($variables), array_values($variables), $this->message);
    }

    /**
     * Check if template can be sent via WhatsApp.
     */
    public function canSendViaWhatsApp(): bool
    {
        return $this->is_active && $this->whatsapp_enabled;
    }
}

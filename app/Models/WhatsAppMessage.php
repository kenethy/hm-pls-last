<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'phone_number',
        'message_type',
        'content',
        'caption',
        'media_path',
        'status',
        'service_id',
        'customer_id',
        'follow_up_template_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
        'api_response',
        'is_automated',
        'triggered_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'api_response' => 'array',
        'is_automated' => 'boolean',
    ];

    /**
     * Get the service that owns the message.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the customer that owns the message.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the follow-up template that was used.
     */
    public function followUpTemplate(): BelongsTo
    {
        return $this->belongsTo(FollowUpTemplate::class);
    }

    /**
     * Scope for pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent messages.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for automated messages.
     */
    public function scopeAutomated($query)
    {
        return $query->where('is_automated', true);
    }

    /**
     * Scope for manual messages.
     */
    public function scopeManual($query)
    {
        return $query->where('is_automated', false);
    }

    /**
     * Mark message as sent.
     */
    public function markAsSent(string $messageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark message as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Mark message as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'sent' => 'info',
            'delivered' => 'success',
            'read' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get status display text.
     */
    public function getStatusDisplay(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'sent' => 'Terkirim',
            'delivered' => 'Diterima',
            'read' => 'Dibaca',
            'failed' => 'Gagal',
            default => 'Unknown',
        };
    }

    /**
     * Get message type display.
     */
    public function getMessageTypeDisplay(): string
    {
        return match ($this->message_type) {
            'text' => 'Teks',
            'image' => 'Gambar',
            'file' => 'File',
            'contact' => 'Kontak',
            'link' => 'Link',
            'location' => 'Lokasi',
            default => 'Unknown',
        };
    }

    /**
     * Get triggered by display.
     */
    public function getTriggeredByDisplay(): string
    {
        return match ($this->triggered_by) {
            'service_completion' => 'Selesai Servis',
            'manual' => 'Manual',
            'scheduled' => 'Terjadwal',
            default => 'Unknown',
        };
    }
}

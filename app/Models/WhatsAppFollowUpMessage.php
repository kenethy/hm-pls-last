<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppFollowUpMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'customer_id',
        'message_template_id',
        'phone',
        'message_content',
        'scheduled_at',
        'sent_at',
        'status',
        'whatsapp_message_id',
        'error_message',
        'response_data',
        'retry_count',
        'last_retry_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'response_data' => 'array',
        'retry_count' => 'integer',
    ];

    /**
     * Get the service associated with this follow-up message.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the customer associated with this follow-up message.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the message template used for this follow-up.
     */
    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    /**
     * Scope for pending messages
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent messages
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for messages ready to be sent
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    /**
     * Mark message as sent
     */
    public function markAsSent($whatsappMessageId = null, $responseData = null)
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'whatsapp_message_id' => $whatsappMessageId,
            'response_data' => $responseData,
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed($errorMessage, $responseData = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'response_data' => $responseData,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Check if message can be retried
     */
    public function canRetry($maxRetries = 3)
    {
        return $this->status === 'failed' && $this->retry_count < $maxRetries;
    }

    /**
     * Reset for retry
     */
    public function resetForRetry()
    {
        $this->update([
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor()
    {
        return match ($this->status) {
            'pending' => 'warning',
            'sent' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'primary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'sent' => 'Terkirim',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            default => 'Unknown',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WhatsAppConfig extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_config';

    protected $fillable = [
        'name',
        'api_url',
        'api_username',
        'api_password',
        'webhook_secret',
        'webhook_url',
        'is_active',
        'auto_reply_enabled',
        'auto_reply_message',
        'connection_status',
        'last_connected_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_reply_enabled' => 'boolean',
        'connection_status' => 'array',
        'last_connected_at' => 'datetime',
    ];

    /**
     * Get the active WhatsApp configuration.
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Check if WhatsApp is properly configured and active.
     */
    public static function isConfigured(): bool
    {
        $config = static::getActive();
        return $config && $config->api_url && $config->is_active;
    }

    /**
     * Get the full API URL with endpoint.
     */
    public function getApiEndpoint(string $endpoint): string
    {
        return rtrim($this->api_url, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Get basic auth credentials if configured.
     */
    public function getBasicAuthCredentials(): ?array
    {
        if ($this->api_username && $this->api_password) {
            return [
                'username' => $this->api_username,
                'password' => $this->api_password,
            ];
        }
        return null;
    }

    /**
     * Update connection status.
     */
    public function updateConnectionStatus(array $status): void
    {
        $this->update([
            'connection_status' => $status,
            'last_connected_at' => now(),
        ]);
    }

    /**
     * Check if connection is healthy.
     */
    public function isConnected(): bool
    {
        if (!$this->connection_status) {
            return false;
        }

        return isset($this->connection_status['connected']) &&
            $this->connection_status['connected'] === true;
    }

    /**
     * Get connection status display.
     */
    protected function connectionStatusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->connection_status) {
                    return 'Unknown';
                }

                if ($this->isConnected()) {
                    return 'Connected';
                }

                return $this->connection_status['status'] ?? 'Disconnected';
            }
        );
    }

    /**
     * Get authentication status display.
     */
    protected function authStatusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->connection_status) {
                    return 'Unknown';
                }

                if (isset($this->connection_status['devices']) && !empty($this->connection_status['devices'])) {
                    return 'Authenticated';
                }

                return 'Not Authenticated';
            }
        );
    }
}

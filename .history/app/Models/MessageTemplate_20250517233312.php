<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageTemplate extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'type',
        'content',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the formatted content with variables replaced by actual values.
     *
     * @param Service $service The service to use for variable replacement
     * @return string The formatted content
     */
    public function getFormattedContent(Service $service): string
    {
        $content = $this->content;

        // Replace variables with actual values
        $replacements = [
            '{customer_name}' => $service->customer_name,
            '{vehicle_model}' => $service->car_model,
            '{license_plate}' => $service->license_plate,
            '{service_date}' => $service->created_at->format('d/m/Y'),
            '{service_type}' => $service->service_type,
            '{service_description}' => $service->description,
            '{invoice_number}' => $service->invoice_number,
            '{service_cost}' => number_format($service->total_cost, 0, ',', '.'),
        ];

        // Add mechanic names if available
        if ($service->mechanics()->exists()) {
            $mechanicNames = $service->mechanics()->pluck('name')->implode(', ');
            $replacements['{mechanic_names}'] = $mechanicNames;
        } else {
            $replacements['{mechanic_names}'] = 'Tim Hartono Motor';
        }

        // Replace all variables
        foreach ($replacements as $variable => $value) {
            $content = str_replace($variable, $value, $content);
        }

        return $content;
    }

    /**
     * Scope a query to only include active templates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}

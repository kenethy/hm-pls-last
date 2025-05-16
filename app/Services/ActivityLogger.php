<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $action The action description
     * @param string $actionType The type of action (create, update, delete, etc.)
     * @param Model|string $resourceType The resource type or model
     * @param int|null $resourceId The resource ID
     * @param string|null $resourceName The resource name or title
     * @param array|null $details Additional details about the action
     * @return ActivityLog
     */
    public static function log(
        string $action,
        string $actionType,
        $resourceType,
        ?int $resourceId = null,
        ?string $resourceName = null,
        ?array $details = null
    ): ActivityLog {
        // Get the resource type from the model if a model is provided
        if ($resourceType instanceof Model) {
            $resourceName = $resourceName ?? self::getResourceName($resourceType);
            $resourceId = $resourceId ?? $resourceType->getKey();
            $resourceType = get_class($resourceType);
        }
        
        // Extract the short class name if it's a fully qualified class name
        if (is_string($resourceType) && str_contains($resourceType, '\\')) {
            $resourceType = class_basename($resourceType);
        }
        
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'action_type' => $actionType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'resource_name' => $resourceName,
            'details' => $details,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
    
    /**
     * Log a create action.
     *
     * @param Model $model The created model
     * @param array|null $details Additional details
     * @return ActivityLog
     */
    public static function logCreate(Model $model, ?array $details = null): ActivityLog
    {
        return self::log(
            'created',
            'create',
            $model,
            null,
            null,
            $details
        );
    }
    
    /**
     * Log an update action.
     *
     * @param Model $model The updated model
     * @param array|null $details Additional details
     * @return ActivityLog
     */
    public static function logUpdate(Model $model, ?array $details = null): ActivityLog
    {
        return self::log(
            'updated',
            'update',
            $model,
            null,
            null,
            $details
        );
    }
    
    /**
     * Log a delete action.
     *
     * @param Model $model The deleted model
     * @param array|null $details Additional details
     * @return ActivityLog
     */
    public static function logDelete(Model $model, ?array $details = null): ActivityLog
    {
        return self::log(
            'deleted',
            'delete',
            $model,
            null,
            null,
            $details
        );
    }
    
    /**
     * Get a readable name for the resource.
     *
     * @param Model $model The model
     * @return string
     */
    protected static function getResourceName(Model $model): string
    {
        // Try to get a name from common attributes
        $nameAttributes = ['name', 'title', 'subject', 'customer_name', 'full_name'];
        
        foreach ($nameAttributes as $attribute) {
            if (isset($model->$attribute)) {
                return $model->$attribute;
            }
        }
        
        // Fall back to the model class and ID
        return class_basename($model) . ' #' . $model->getKey();
    }
}

<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        // Only log actions by staff users
        if (Auth::check() && Auth::user()->isStaff()) {
            ActivityLogger::logCreate($model);
        }
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Only log actions by staff users
        if (Auth::check() && Auth::user()->isStaff()) {
            // Get the changed attributes
            $changes = [];
            foreach ($model->getDirty() as $key => $value) {
                $original = $model->getOriginal($key);
                $changes[$key] = [
                    'old' => $original,
                    'new' => $value,
                ];
            }
            
            ActivityLogger::logUpdate($model, ['changes' => $changes]);
        }
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        // Only log actions by staff users
        if (Auth::check() && Auth::user()->isStaff()) {
            ActivityLogger::logDelete($model);
        }
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        // Only log actions by staff users
        if (Auth::check() && Auth::user()->isStaff()) {
            ActivityLogger::log('restored', 'restore', $model);
        }
    }

    /**
     * Handle the Model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        // Only log actions by staff users
        if (Auth::check() && Auth::user()->isStaff()) {
            ActivityLogger::log('force deleted', 'force_delete', $model);
        }
    }
}

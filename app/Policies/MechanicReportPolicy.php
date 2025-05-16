<?php

namespace App\Policies;

use App\Models\MechanicReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MechanicReportPolicy extends BasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MechanicReport $mechanicReport): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MechanicReport $mechanicReport): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MechanicReport $mechanicReport): bool
    {
        // Only admin users can delete mechanic reports
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MechanicReport $mechanicReport): bool
    {
        // Only admin users can restore mechanic reports
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MechanicReport $mechanicReport): bool
    {
        // Only admin users can force delete mechanic reports
        return $user->isAdmin();
    }
}

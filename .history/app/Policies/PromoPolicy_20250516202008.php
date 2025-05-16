<?php

namespace App\Policies;

use App\Models\Promo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromoPolicy extends BasePolicy
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
     *
     * @param User $user
     * @param Promo|mixed $model
     */
    public function view(User $user, $model): bool
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
     *
     * @param User $user
     * @param Promo|mixed $model
     */
    public function update(User $user, $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param Promo|mixed $model
     */
    public function delete(User $user, $model): bool
    {
        // Only admin users can delete promos
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param Promo|mixed $model
     */
    public function restore(User $user, $model): bool
    {
        // Only admin users can restore promos
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param Promo|mixed $model
     */
    public function forceDelete(User $user, $model): bool
    {
        // Only admin users can force delete promos
        return $user->isAdmin();
    }
}

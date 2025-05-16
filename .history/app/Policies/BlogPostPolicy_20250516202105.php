<?php

namespace App\Policies;

// use App\Models\BlogPost; // Commented out as we're using mixed type for compatibility
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogPostPolicy extends BasePolicy
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
     * @param BlogPost|mixed $model
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
     * @param BlogPost|mixed $model
     */
    public function update(User $user, $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param BlogPost|mixed $model
     */
    public function delete(User $user, $model): bool
    {
        // Only admin users can delete blog posts
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param BlogPost|mixed $model
     */
    public function restore(User $user, $model): bool
    {
        // Only admin users can restore blog posts
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param BlogPost|mixed $model
     */
    public function forceDelete(User $user, $model): bool
    {
        // Only admin users can force delete blog posts
        return $user->isAdmin();
    }
}

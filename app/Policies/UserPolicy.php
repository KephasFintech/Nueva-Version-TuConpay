<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determina si el usuario puede ver la lista de usuarios.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::EXTERNAL_ADMIN->value,
        ]);
    }

    /**
     * Determina si el usuario puede ver a otro usuario.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::EXTERNAL_ADMIN->value,
        ]);
    }

    /**
     * Determina si el usuario puede crear usuarios.
     */
    public function create(User $user): bool
    {
        return $user->hasRole([
            UserRole::SUPER_ADMIN->value,
            UserRole::EXTERNAL_ADMIN->value,
        ]);
    }

    /**
     * Determina si el usuario puede actualizar a otro usuario.
     */
    public function update(User $user, User $model): bool
    {
        // Un usuario puede actualizarse a sí mismo
        if ($user->id === $model->id) {
            return true;
        }

        // El Super Admin puede actualizar a cualquiera excepto a otro Super Admin (salvo que sea él mismo)
        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            if ($model->hasRole(UserRole::SUPER_ADMIN->value)) {
                return false; // Solo el propio super_admin puede editarse a sí mismo
            }
            return true;
        }

        return false;
    }

    /**
     * Determina si el usuario puede eliminar a otro usuario (soft delete).
     */
    public function delete(User $user, User $model): bool
    {
        // Nadie puede eliminarse a sí mismo
        if ($user->id === $model->id) {
            return false;
        }

        // El Super Admin puede eliminar a cualquiera menos a otro Super Admin
        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            return !$model->hasRole(UserRole::SUPER_ADMIN->value);
        }

        return false;
    }
}

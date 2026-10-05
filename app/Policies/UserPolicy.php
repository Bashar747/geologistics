<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']);
    }

    public function view(User $user, User $model): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']) || $user->id === $model->id;
    }

    public function update(User $user, User $model): bool
    {
        return in_array($user->role, ['admin', 'dispatcher']) || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return in_array($user->role, ['admin']);
    }
}

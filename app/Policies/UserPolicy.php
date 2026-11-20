<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $subject): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $subject): bool
    {
        return $actor->isAdmin() && ! $actor->is($subject);
    }

    public function delete(User $actor, User $subject): bool
    {
        if (! $actor->isAdmin() || $actor->is($subject)) {
            return false;
        }

        if ($subject->role === UserRole::Admin) {
            return User::query()->where('role', UserRole::Admin)->count() > 1;
        }

        return true;
    }
}

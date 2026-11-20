<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesCatalog();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->managesCatalog();
    }

    public function create(User $user): bool
    {
        return $user->managesCatalog();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->managesCatalog();
    }
}

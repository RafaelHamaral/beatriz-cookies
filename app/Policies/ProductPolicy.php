<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesCatalog();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->managesCatalog();
    }

    public function create(User $user): bool
    {
        return $user->managesCatalog();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->managesCatalog();
    }
}

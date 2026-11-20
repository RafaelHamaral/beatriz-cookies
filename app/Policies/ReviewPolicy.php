<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesCatalog();
    }

    public function view(User $user, Review $review): bool
    {
        return $user->managesCatalog();
    }

    public function update(User $user, Review $review): bool
    {
        return $user->managesCatalog();
    }
}

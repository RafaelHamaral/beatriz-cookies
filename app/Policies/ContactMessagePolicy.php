<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesCatalog();
    }

    public function view(User $user, ContactMessage $message): bool
    {
        return $user->managesCatalog();
    }

    public function update(User $user, ContactMessage $message): bool
    {
        return $user->managesCatalog();
    }
}

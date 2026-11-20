<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool { return $user->managesCatalog(); }
    public function view(User $user, Order $order): bool { return $user->managesCatalog(); }
    public function update(User $user, Order $order): bool { return $user->managesCatalog(); }
    public function payment(User $user, Order $order): bool { return $user->isAdmin(); }
}

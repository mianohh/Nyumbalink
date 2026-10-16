<?php

namespace App\Policies;

use App\Models\House;
use App\Models\User;

class HousePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, House $house): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, House $house): bool
    {
        return true;
    }

    public function delete(User $user, House $house): bool
    {
        return $user->isAdmin();
    }
}

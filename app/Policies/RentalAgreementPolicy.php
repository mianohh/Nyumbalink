<?php

namespace App\Policies;

use App\Models\RentalAgreement;
use App\Models\User;

class RentalAgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RentalAgreement $agreement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, RentalAgreement $agreement): bool
    {
        return true;
    }

    public function delete(User $user, RentalAgreement $agreement): bool
    {
        return $user->isAdmin();
    }

    public function terminate(User $user, RentalAgreement $agreement): bool
    {
        return true;
    }
}

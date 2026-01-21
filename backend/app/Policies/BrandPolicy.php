<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Brand $brand): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBusiness()) {
            return $user->companies()->whereKey($brand->company_id)->exists();
        }

        return false;
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->isAdmin();
    }
}

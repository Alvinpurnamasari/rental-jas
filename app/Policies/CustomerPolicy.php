<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        // Sales hanya boleh melihat, bukan mengubah
        return true;
    }


    public function view(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->canAccessBranch($customer->branch_id);
    }

    public function create(User $user): bool
    {
        // Sales dapat membuat customer di branch mereka
        return $user->isSuperAdmin() || $user->role === 'admin_toko' || $user->role === 'sales';
    }


    public function update(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->role === 'sales') return $user->canAccessBranch($customer->branch_id);
        return $user->canAccessBranch($customer->branch_id);
    }


    public function blacklist(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->role === 'sales') return $user->canAccessBranch($customer->branch_id);
        return $user->canAccessBranch($customer->branch_id);
    }


    public function delete(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->role === 'sales') return false;
        return $user->canAccessBranch($customer->branch_id);
    }
}
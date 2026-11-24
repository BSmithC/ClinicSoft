<?php

namespace App\Policies;

use App\Models\Specialty;
use App\Models\User;

class SpecialtyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function view(User $user, Specialty $specialty): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Specialty $specialty): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Specialty $specialty): bool
    {
        return $user->isAdmin();
    }
}

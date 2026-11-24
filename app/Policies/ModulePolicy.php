<?php

namespace App\Policies;

use App\Models\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->isStaff($user);
    }

    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }
}

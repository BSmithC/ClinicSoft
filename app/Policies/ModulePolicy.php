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
        // These modules are hidden from doctors in the UI, so the backend must
        // enforce the same boundary instead of relying on navigation only.
        return $user->isAdmin();
    }
}

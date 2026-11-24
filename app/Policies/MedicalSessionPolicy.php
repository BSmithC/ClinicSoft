<?php

namespace App\Policies;

use App\Models\MedicalSession;
use App\Models\User;

class MedicalSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function view(User $user, MedicalSession $session): bool
    {
        return $user->isAdmin() || (int) $session->doctor_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function update(User $user, MedicalSession $session): bool
    {
        return $user->isAdmin() || (int) $session->doctor_id === (int) $user->id;
    }

    public function delete(User $user, MedicalSession $session): bool
    {
        return $this->update($user, $session);
    }
}

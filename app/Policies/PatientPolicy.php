<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $patient->Appointment()
            ->where('doctor_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }
}

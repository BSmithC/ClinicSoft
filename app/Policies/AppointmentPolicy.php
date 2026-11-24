<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin() || (int) $appointment->doctor_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }
}

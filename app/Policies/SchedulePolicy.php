<?php

namespace App\Policies;

use App\Models\BlockedDate;
use App\Models\SessionBreak;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor']);
    }

    public function update(User $user, BlockedDate|SessionBreak $model): bool
    {
        return $this->ownsSchedule($user, $model);
    }

    public function delete(User $user, BlockedDate|SessionBreak $model): bool
    {
        return $this->ownsSchedule($user, $model);
    }

    private function ownsSchedule(User $user, BlockedDate|SessionBreak $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($model instanceof BlockedDate) {
            return (int) $model->doctor_id === (int) $user->id;
        }

        return (int) $model->session?->doctor_id === (int) $user->id;
    }
}

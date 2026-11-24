<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for appointment state transitions.
 */
final class AppointmentStatusMachine
{
    public static function canTransition(AppointmentStatus $from, AppointmentStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, $from->allowedTransitions(), true);
    }

    public static function assertTransition(Appointment $appointment, AppointmentStatus $to): void
    {
        $from = $appointment->status;

        if (! $from instanceof AppointmentStatus) {
            $from = AppointmentStatus::from((string) $from);
        }

        if (! self::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => [sprintf(
                    'Transición no permitida: de "%s" a "%s".',
                    $from->label(),
                    $to->label()
                )],
            ]);
        }
    }
}

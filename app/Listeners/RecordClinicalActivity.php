<?php

namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentStatusChanged;
use App\Events\MedicalSessionCancelled;
use App\Jobs\LogActivityJob;
use Illuminate\Support\Facades\Auth;

class RecordClinicalActivity
{
    public function handle(object $event): void
    {
        $userId = Auth::id();

        match (true) {
            $event instanceof AppointmentBooked => LogActivityJob::dispatch(
                'appointment.booked',
                $userId,
                'appointment',
                $event->appointment->id,
                ['doctor_id' => $event->appointment->doctor_id, 'date' => $event->appointment->date?->toDateString()]
            ),
            $event instanceof AppointmentCancelled => LogActivityJob::dispatch(
                'appointment.cancelled',
                $userId,
                'appointment',
                $event->appointment->id,
                ['reason' => $event->reason]
            ),
            $event instanceof AppointmentStatusChanged => LogActivityJob::dispatch(
                'appointment.status_changed',
                $userId,
                'appointment',
                $event->appointment->id,
                ['from' => $event->from, 'to' => $event->to, 'reason' => $event->reason]
            ),
            $event instanceof MedicalSessionCancelled => LogActivityJob::dispatch(
                'session.cancelled',
                $userId,
                'medical_session',
                $event->session->id,
                ['reason' => $event->reason]
            ),
            default => null,
        };
    }
}

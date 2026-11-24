<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\MedicalSessionStatus;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

/**
 * Calculates real availability from sessions, breaks, blocked dates and bookings.
 * Vue only renders these results; no business rule is duplicated in the client.
 */
final class AvailabilityService
{
    public function __construct(private readonly AppointmentAvailabilityService $legacy)
    {
    }

    /**
     * Backwards-compatible validation for appointments not linked to a session.
     *
     * @return array<int, string>
     */
    public function validateLegacy(
        int $doctorId,
        string $date,
        string $start,
        string $end,
        ?int $patientId = null,
        ?int $ignoreId = null
    ): array {
        $errors = $this->legacy->validate($doctorId, $date, $start, $end, $ignoreId);

        if (BlockedDate::query()->where('doctor_id', $doctorId)->whereDate('date', $date)->exists()) {
            $errors[] = 'La fecha está bloqueada para este médico.';
        }

        $doctor = User::find($doctorId);

        if (! $doctor || ! $doctor->hasRole('doctor')) {
            $errors[] = 'El médico seleccionado no existe o no tiene rol médico.';
        } elseif ($doctor->doctorProfile()->exists() && ! $doctor->doctorProfile->active) {
            $errors[] = 'El médico seleccionado está inactivo.';
        }

        if ($patientId && ! Patient::find($patientId)) {
            $errors[] = 'El paciente seleccionado no existe.';
        }

        if ($patientId && $this->patientHasConflict($patientId, $date, $start, $end, $ignoreId)) {
            $errors[] = 'El paciente ya tiene una cita en ese horario.';
        }

        return $errors;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function slotsForSession(
        MedicalSession $session,
        ?int $patientId = null,
        ?int $ignoreId = null
    ): array {
        $session->loadMissing('breaks', 'appointments');
        $blocked = BlockedDate::query()
            ->where('doctor_id', $session->doctor_id)
            ->whereDate('date', $session->date)
            ->exists();

        $step = max(1, (int) $session->consultation_minutes);
        $cursor = $this->toMinutes($session->start_time);
        $finish = $this->toMinutes($session->end_time);
        $bookings = $session->appointments
            ->filter(fn (Appointment $appointment) => $appointment->active
                && ! $appointment->status->isTerminal()
                && $appointment->id !== $ignoreId)
            ->values();
        $slots = [];

        while ($cursor + $step <= $finish) {
            $start = $this->toTime($cursor);
            $end = $this->toTime($cursor + $step);
            $conflict = $bookings->first(fn (Appointment $appointment) => $this->overlaps(
                $start,
                $end,
                $appointment->starttime,
                $appointment->endtime
            ));
            $samePatientConflict = $patientId && $this->patientHasConflict(
                $patientId,
                $session->date->toDateString(),
                $start,
                $end,
                $ignoreId
            );
            $inBreak = $session->breaks->contains(fn ($break) => $this->overlaps(
                $start,
                $end,
                $break->start_time,
                $break->end_time
            ));

            $reason = null;
            $reasonCode = null;
            if (! $session->status instanceof MedicalSessionStatus) {
                $session->status = MedicalSessionStatus::from((string) $session->status);
            }
            if (! $session->status->acceptsBookings()) {
                $reasonCode = 'session_closed';
                $reason = 'La sesión no admite reservas.';
            } elseif ($blocked) {
                $reasonCode = 'blocked';
                $reason = 'La fecha está bloqueada para este médico.';
            } elseif ($inBreak) {
                $reasonCode = 'break';
                $reason = 'Horario de descanso.';
            } elseif ($bookings->count() >= (int) $session->capacity) {
                $reasonCode = 'capacity_full';
                $reason = 'Capacidad completa.';
            } elseif ($conflict) {
                $reasonCode = 'occupied';
                $reason = 'Horario ocupado.';
            } elseif ($samePatientConflict) {
                $reasonCode = 'patient_conflict';
                $reason = 'El paciente ya tiene una cita en ese horario.';
            }

            $slots[] = [
                'start' => $start,
                'end' => $end,
                'available' => $reason === null,
                'reason' => $reason,
                'reasonCode' => $reasonCode,
            ];

            $cursor += $step;
        }

        return $slots;
    }

    /**
     * Revalidates a concrete slot inside a session. Must be called inside a transaction.
     *
     * @return array<int, string>
     */
    public function validateSessionSlot(
        MedicalSession $session,
        string $start,
        string $end,
        ?int $patientId = null,
        ?int $ignoreId = null
    ): array {
        $errors = [];
        $startMinutes = $this->toMinutes($start);
        $endMinutes = $this->toMinutes($end);
        $sessionStart = $this->toMinutes($session->start_time);
        $sessionEnd = $this->toMinutes($session->end_time);

        if ($endMinutes <= $startMinutes) {
            $errors[] = 'La hora de fin debe ser posterior a la de inicio.';
        }

        $status = $session->status;
        if (! $status instanceof MedicalSessionStatus) {
            $status = MedicalSessionStatus::from((string) $status);
        }
        if (! $status->acceptsBookings()) {
            $errors[] = 'La sesión no admite reservas.';
        }

        if ($startMinutes < $sessionStart || $endMinutes > $sessionEnd) {
            $errors[] = 'El horario está fuera de la sesión médica.';
        }

        $step = max(1, (int) $session->consultation_minutes);
        if (($startMinutes - $sessionStart) % $step !== 0 || ($endMinutes - $startMinutes) !== $step) {
            $errors[] = 'El horario debe respetar la duración y la estructura de la sesión.';
        }

        if ($session->breaks()->where('start_time', '<', $end)->where('end_time', '>', $start)->exists()) {
            $errors[] = 'El horario coincide con un descanso de la sesión.';
        }

        if (BlockedDate::query()
            ->where('doctor_id', $session->doctor_id)
            ->whereDate('date', $session->date)
            ->exists()) {
            $errors[] = 'La fecha está bloqueada para este médico.';
        }

        $doctor = User::find($session->doctor_id);
        if (! $doctor || ! $doctor->hasRole('doctor')) {
            $errors[] = 'El médico de la sesión no es válido.';
        } elseif ($doctor->doctorProfile()->exists() && ! $doctor->doctorProfile->active) {
            $errors[] = 'El médico de la sesión está inactivo.';
        }

        $active = Appointment::query()
            ->active()
            ->notTerminal()
            ->where('session_id', $session->id)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->lockForUpdate()
            ->get(['starttime', 'endtime']);

        if ($active->count() >= (int) $session->capacity) {
            $errors[] = 'La sesión ya alcanzó su capacidad.';
        }

        if ($active->contains(fn (Appointment $appointment) => $this->overlaps(
            $start,
            $end,
            $appointment->starttime,
            $appointment->endtime
        ))) {
            $errors[] = 'Ya existe una cita en ese horario de la sesión.';
        }

        if (Appointment::query()
            ->active()
            ->notTerminal()
            ->forDoctor($session->doctor_id)
            ->onDate($session->date->toDateString())
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $errors[] = 'El médico ya tiene una cita que se solapa en ese horario.';
        }

        if ($patientId && $this->patientHasConflict($patientId, $session->date->toDateString(), $start, $end, $ignoreId)) {
            $errors[] = 'El paciente ya tiene una cita en ese horario.';
        }

        if ($patientId && ! Patient::find($patientId)) {
            $errors[] = 'El paciente seleccionado no existe.';
        }

        return array_values(array_unique($errors));
    }

    private function patientHasConflict(
        int $patientId,
        string $date,
        string $start,
        string $end,
        ?int $ignoreId = null
    ): bool {
        return Appointment::query()
            ->active()
            ->notTerminal()
            ->where('patient_id', $patientId)
            ->onDate($date)
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function overlaps(string $start, string $end, string $otherStart, string $otherEnd): bool
    {
        return $this->toMinutes($start) < $this->toMinutes($otherEnd)
            && $this->toMinutes($end) > $this->toMinutes($otherStart);
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);

        return ((int) $hours * 60) + (int) $minutes;
    }

    private function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}

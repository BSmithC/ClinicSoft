<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentStatusChanged;
use App\Models\Appointment;
use App\Models\MedicalSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All appointment writes go through this service: validation and persistence
 * happen inside the same transaction and advisory lock.
 */
final class AppointmentBookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ConcurrencyLock $lock,
        private readonly AppointmentStatusMachine $statusMachine,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function book(array $data): Appointment
    {
        $data['starttime'] = $this->normalizeTime($data['starttime']);
        $data['endtime'] = $this->normalizeTime($data['endtime']);
        $doctorId = (int) $data['doctor_id'];
        $date = $data['date'];

        // A session can override the requested doctor/date, so compute the lock
        // keys from the persisted session before entering the transaction.
        $sessionPreview = ! empty($data['session_id'])
            ? MedicalSession::query()->find($data['session_id'])
            : null;

        if ($sessionPreview) {
            $doctorId = (int) $sessionPreview->doctor_id;
            $date = $sessionPreview->date->toDateString();
        }

        $keys = [
            "booking:{$doctorId}:{$date}",
            'patient:' . (int) $data['patient_id'] . ":{$date}",
        ];

        if ($sessionPreview
            && ((int) $data['doctor_id'] !== $doctorId || $data['date'] !== $date)) {
            $keys[] = 'booking:' . (int) $data['doctor_id'] . ':' . $data['date'];
        }

        return $this->lock->runMany($keys, function () use ($data) {
            return DB::transaction(function () use ($data) {
                $session = null;
                $patientId = (int) $data['patient_id'];

                if (! empty($data['session_id'])) {
                    $session = MedicalSession::whereKey($data['session_id'])->lockForUpdate()->firstOrFail();
                    $data['doctor_id'] = $session->doctor_id;
                    $data['date'] = $session->date->toDateString();
                    $data['specialty_id'] = $session->specialty_id;
                    $errors = $this->availability->validateSessionSlot(
                        $session,
                        $data['starttime'],
                        $data['endtime'],
                        $patientId
                    );
                } else {
                    $errors = $this->availability->validateLegacy(
                        (int) $data['doctor_id'],
                        $data['date'],
                        $data['starttime'],
                        $data['endtime'],
                        $patientId
                    );
                }

                if ($errors !== []) {
                    throw ValidationException::withMessages(['starttime' => $errors]);
                }

                $status = AppointmentStatus::from($data['status'] ?? AppointmentStatus::Pending->value);

                if ($session && ! in_array($status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Una reserva de sesión solo puede crearse como pendiente o confirmada.',
                    ]);
                }

                $appointment = Appointment::create([
                    'title' => $data['title'],
                    'notes' => $data['notes'] ?? null,
                    'doctor_id' => $data['doctor_id'],
                    'patient_id' => $patientId,
                    'drug_id' => $data['drug_id'] ?? null,
                    'session_id' => $session?->id,
                    'specialty_id' => $data['specialty_id'] ?? null,
                    'status' => $status,
                    'attended' => ($data['attended'] ?? false) || $status === AppointmentStatus::Completed,
                    'date' => $data['date'],
                    'starttime' => $data['starttime'],
                    'endtime' => $data['endtime'],
                    'active' => ($data['active'] ?? true) && ! $status->isTerminal(),
                ]);

                $appointment->histories()->create([
                    'to_status' => $status->value,
                    'actor_id' => auth()->id(),
                    'reason' => 'Cita creada desde el sistema.',
                ]);

                AppointmentBooked::dispatch($appointment);

                return $appointment;
            });
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function reschedule(Appointment $appointment, array $data): Appointment
    {
        if ($appointment->status->isTerminal()) {
            throw ValidationException::withMessages(['status' => 'Una cita histórica no se puede reprogramar.']);
        }

        $doctorId = (int) ($data['doctor_id'] ?? $appointment->doctor_id);
        $date = $data['date'] ?? $this->dateKey($appointment->date);
        $data['starttime'] = $this->normalizeTime($data['starttime'] ?? $appointment->starttime);
        $data['endtime'] = $this->normalizeTime($data['endtime'] ?? $appointment->endtime);

        $sessionPreview = ! empty($data['session_id'])
            ? MedicalSession::query()->find($data['session_id'])
            : null;

        if ($sessionPreview) {
            $doctorId = (int) $sessionPreview->doctor_id;
            $date = $sessionPreview->date->toDateString();
        }

        // Lock both the origin and the destination so a move between doctors
        // cannot race with a new booking in either agenda.
        $keys = array_merge($this->appointmentLockKeys($appointment), [
            "booking:{$doctorId}:{$date}",
            'patient:' . (int) ($data['patient_id'] ?? $appointment->patient_id) . ":{$date}",
        ]);

        return $this->lock->runMany($keys, function () use ($appointment, $data, $doctorId, $date) {
            return DB::transaction(function () use ($appointment, $data, $doctorId, $date) {
                $session = null;
                if (! empty($data['session_id'])) {
                    $session = MedicalSession::whereKey($data['session_id'])->lockForUpdate()->firstOrFail();
                    $doctorId = $session->doctor_id;
                    $date = $session->date->toDateString();
                    $data['doctor_id'] = $doctorId;
                    $data['date'] = $date;
                    $data['specialty_id'] = $session->specialty_id;
                    $errors = $this->availability->validateSessionSlot(
                        $session,
                        $data['starttime'],
                        $data['endtime'],
                        (int) ($data['patient_id'] ?? $appointment->patient_id),
                        $appointment->id
                    );
                } else {
                    $errors = $this->availability->validateLegacy(
                        $doctorId,
                        $date,
                        $data['starttime'],
                        $data['endtime'],
                        (int) ($data['patient_id'] ?? $appointment->patient_id),
                        $appointment->id
                    );
                }

                if ($errors !== []) {
                    throw ValidationException::withMessages(['starttime' => $errors]);
                }

                $appointment->histories()->create([
                    'from_status' => $appointment->status->value,
                    'to_status' => $appointment->status->value,
                    'from_date' => $appointment->date,
                    'to_date' => $date,
                    'from_start' => $appointment->starttime,
                    'to_start' => $data['starttime'],
                    'from_end' => $appointment->endtime,
                    'to_end' => $data['endtime'],
                    'actor_id' => auth()->id(),
                    'reason' => $data['reason'] ?? 'Cita reprogramada.',
                ]);

                $appointment->update([
                    'title' => $data['title'] ?? $appointment->title,
                    'notes' => $data['notes'] ?? $appointment->notes,
                    'doctor_id' => $doctorId,
                    'patient_id' => $data['patient_id'] ?? $appointment->patient_id,
                    'drug_id' => $data['drug_id'] ?? $appointment->drug_id,
                    'session_id' => $session?->id,
                    'specialty_id' => $data['specialty_id'] ?? $appointment->specialty_id,
                    'date' => $date,
                    'starttime' => $data['starttime'],
                    'endtime' => $data['endtime'],
                    'active' => $data['active'] ?? $appointment->active,
                    'attended' => $data['attended'] ?? $appointment->attended,
                ]);

                return $appointment->fresh();
            });
        });
    }

    public function changeStatus(Appointment $appointment, AppointmentStatus $status, ?string $reason = null): Appointment
    {
        return $this->lock->runMany($this->appointmentLockKeys($appointment), function () use ($appointment, $status, $reason) {
            return DB::transaction(function () use ($appointment, $status, $reason) {
                $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
                $from = $locked->status;

                self::assertStatus($this->statusMachine, $locked, $status);

                if ($from === $status) {
                    return $locked;
                }

                $locked->histories()->create([
                    'from_status' => $from->value,
                    'to_status' => $status->value,
                    'actor_id' => auth()->id(),
                    'reason' => $reason,
                ]);

                $isCancelled = $status === AppointmentStatus::Cancelled;

                $locked->update([
                    'status' => $status,
                    'attended' => $status === AppointmentStatus::Completed ? true : $locked->attended,
                    'active' => $status->isTerminal() ? false : $locked->active,
                    'cancelled_at' => $isCancelled ? now() : $locked->cancelled_at,
                ]);

                AppointmentStatusChanged::dispatch($locked->fresh(), $from->value, $status->value, $reason);

                return $locked->fresh();
            });
        });
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        return $this->lock->runMany($this->appointmentLockKeys($appointment), function () use ($appointment, $reason) {
            return DB::transaction(function () use ($appointment, $reason) {
                $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
                $from = $locked->status;

                self::assertStatus($this->statusMachine, $locked, AppointmentStatus::Cancelled);

                $locked->histories()->create([
                    'from_status' => $from->value,
                    'to_status' => AppointmentStatus::Cancelled->value,
                    'actor_id' => auth()->id(),
                    'reason' => $reason ?: 'Cita cancelada.',
                ]);

                $locked->update([
                    'status' => AppointmentStatus::Cancelled,
                    'active' => false,
                    'cancelled_at' => now(),
                ]);

                AppointmentCancelled::dispatch($locked->fresh(), $reason);

                return $locked->fresh();
            });
        });
    }

    private static function assertStatus(
        AppointmentStatusMachine $machine,
        Appointment $appointment,
        AppointmentStatus $status
    ): void {
        $machine::assertTransition($appointment, $status);
    }

    /**
     * @return array<int, string>
     */
    private function appointmentLockKeys(Appointment $appointment): array
    {
        $date = $this->dateKey($appointment->date);

        return [
            'booking:' . (int) $appointment->doctor_id . ":{$date}",
            'patient:' . (int) $appointment->patient_id . ":{$date}",
        ];
    }

    private function dateKey(mixed $date): string
    {
        return $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : substr((string) $date, 0, 10);
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }
}

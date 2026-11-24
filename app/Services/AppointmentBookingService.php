<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentStatusChanged;
use App\Models\Appointment;
use App\Models\MedicalSession;
use Illuminate\Support\Facades\Auth;
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
            $this->assertSessionScope($sessionPreview);
            $doctorId = (int) $sessionPreview->doctor_id;
            $date = $sessionPreview->date->toDateString();
        }

        $this->assertDoctorScope($doctorId);

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
                    $this->assertSessionScope($session);
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
                    'attended' => $status === AppointmentStatus::Completed,
                    'date' => $data['date'],
                    'starttime' => $data['starttime'],
                    'endtime' => $data['endtime'],
                    'active' => ! $status->isTerminal(),
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
            $this->assertSessionScope($sessionPreview);
            $doctorId = (int) $sessionPreview->doctor_id;
            $date = $sessionPreview->date->toDateString();
        }

        $this->assertDoctorScope($doctorId);

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
                    $this->assertSessionScope($session);
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
                    'active' => ! $appointment->status->isTerminal(),
                    'attended' => $appointment->attended,
                ]);

                return $appointment->fresh();
            });
        });
    }

    /**
     * Updates status and scheduling in one atomic operation. This is the entry
     * point used by AppointmentController::update() so a failed reschedule can
     * never leave a partial status write behind.
     *
     * @param array<string, mixed> $data
     */
    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        $requestedStatus = isset($data['status']) && $data['status'] !== ''
            ? AppointmentStatus::from($data['status'])
            : null;
        $reason = $data['reason'] ?? null;

        unset($data['status'], $data['reason'], $data['active'], $data['attended']);

        if ($requestedStatus) {
            $this->statusMachine::assertTransition($appointment, $requestedStatus);
        }

        $data['starttime'] = $this->normalizeTime($data['starttime'] ?? $appointment->starttime);
        $data['endtime'] = $this->normalizeTime($data['endtime'] ?? $appointment->endtime);
        $patientId = (int) ($data['patient_id'] ?? $appointment->patient_id);
        // A session-bound appointment must keep its clinical session unless the
        // caller explicitly selects another one; dropping it would bypass all
        // session, break, block and capacity rules.
        $sessionId = ! empty($data['session_id'])
            ? (int) $data['session_id']
            : ($appointment->session_id ? (int) $appointment->session_id : null);

        $sessionPreview = $sessionId
            ? MedicalSession::query()->find($sessionId)
            : null;

        if ($sessionId && ! $sessionPreview) {
            throw ValidationException::withMessages(['session_id' => 'La sesión seleccionada no existe.']);
        }

        if ($sessionPreview) {
            $this->assertSessionScope($sessionPreview);
            $doctorId = (int) $sessionPreview->doctor_id;
            $date = $sessionPreview->date->toDateString();
            $data['doctor_id'] = $doctorId;
            $data['date'] = $date;
            $data['specialty_id'] = $sessionPreview->specialty_id;
        } else {
            $doctorId = (int) ($data['doctor_id'] ?? $appointment->doctor_id);
            $date = $data['date'] ?? $this->dateKey($appointment->date);
            $this->assertDoctorScope($doctorId);
        }

        $schedulingChanged = $doctorId !== (int) $appointment->doctor_id
            || $date !== $this->dateKey($appointment->date)
            || $patientId !== (int) $appointment->patient_id
            || $sessionId !== ($appointment->session_id ? (int) $appointment->session_id : null)
            || $data['starttime'] !== $this->normalizeTime($appointment->starttime)
            || $data['endtime'] !== $this->normalizeTime($appointment->endtime);

        if ($requestedStatus?->isTerminal() && $schedulingChanged) {
            throw ValidationException::withMessages([
                'status' => 'No se puede reprogramar una cita cuyo estado final es terminal.',
            ]);
        }

        $keys = array_merge($this->appointmentLockKeys($appointment), [
            "booking:{$doctorId}:{$date}",
            "patient:{$patientId}:{$date}",
        ]);

        return $this->lock->runMany($keys, function () use (
            $appointment,
            $data,
            $requestedStatus,
            $reason,
            $sessionId,
            $doctorId,
            $date,
            $patientId,
            $schedulingChanged
        ) {
            return DB::transaction(function () use (
                $appointment,
                $data,
                $requestedStatus,
                $reason,
                $sessionId,
                $doctorId,
                $date,
                $patientId,
                $schedulingChanged
            ) {
                $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

                if ($requestedStatus) {
                    $this->statusMachine::assertTransition($locked, $requestedStatus);
                }

                $session = null;
                if ($sessionId) {
                    $session = MedicalSession::whereKey($sessionId)->lockForUpdate()->firstOrFail();
                    $this->assertSessionScope($session);
                    $doctorId = (int) $session->doctor_id;
                    $date = $session->date->toDateString();
                    $data['doctor_id'] = $doctorId;
                    $data['date'] = $date;
                    $data['specialty_id'] = $session->specialty_id;
                    $errors = $this->availability->validateSessionSlot(
                        $session,
                        $data['starttime'],
                        $data['endtime'],
                        $patientId,
                        $locked->id
                    );
                } else {
                    $errors = $this->availability->validateLegacy(
                        $doctorId,
                        $date,
                        $data['starttime'],
                        $data['endtime'],
                        $patientId,
                        $locked->id
                    );
                }

                if ($errors !== []) {
                    throw ValidationException::withMessages(['starttime' => $errors]);
                }

                $finalStatus = $requestedStatus ?? $locked->status;

                if ($finalStatus->isTerminal() && $schedulingChanged) {
                    throw ValidationException::withMessages([
                        'status' => 'No se puede reprogramar una cita cuyo estado final es terminal.',
                    ]);
                }

                $statusChanged = $requestedStatus !== null && $requestedStatus !== $locked->status;

                if ($statusChanged) {
                    $locked->histories()->create([
                        'from_status' => $locked->status->value,
                        'to_status' => $requestedStatus->value,
                        'actor_id' => auth()->id(),
                        'reason' => $reason,
                    ]);
                }

                if ($schedulingChanged) {
                    $locked->histories()->create([
                        'from_status' => $locked->status->value,
                        'to_status' => $locked->status->value,
                        'from_date' => $locked->date,
                        'to_date' => $date,
                        'from_start' => $locked->starttime,
                        'to_start' => $data['starttime'],
                        'from_end' => $locked->endtime,
                        'to_end' => $data['endtime'],
                        'actor_id' => auth()->id(),
                        'reason' => $reason ?: 'Cita reprogramada.',
                    ]);
                }

                $locked->update([
                    'title' => $data['title'] ?? $locked->title,
                    'notes' => $data['notes'] ?? $locked->notes,
                    'doctor_id' => $doctorId,
                    'patient_id' => $patientId,
                    'drug_id' => $data['drug_id'] ?? $locked->drug_id,
                    'session_id' => $session?->id,
                    'specialty_id' => $data['specialty_id'] ?? $locked->specialty_id,
                    'status' => $finalStatus,
                    'date' => $date,
                    'starttime' => $data['starttime'],
                    'endtime' => $data['endtime'],
                    'attended' => $finalStatus === AppointmentStatus::Completed ? true : $locked->attended,
                    'active' => ! $finalStatus->isTerminal(),
                    'cancelled_at' => $finalStatus === AppointmentStatus::Cancelled
                        ? ($locked->cancelled_at ?? now())
                        : $locked->cancelled_at,
                ]);

                $updated = $locked->fresh();

                if ($statusChanged && $finalStatus === AppointmentStatus::Cancelled) {
                    AppointmentCancelled::dispatch($updated, $reason);
                } elseif ($statusChanged) {
                    AppointmentStatusChanged::dispatch(
                        $updated,
                        $appointment->status->value,
                        $finalStatus->value,
                        $reason
                    );
                }

                return $updated;
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

                if ($locked->status === AppointmentStatus::Cancelled) {
                    return $locked;
                }

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

    private function assertDoctorScope(int $doctorId): void
    {
        $user = Auth::user();

        if ($user && ! $user->isAdmin() && $doctorId !== (int) $user->id) {
            abort(403, 'No puedes gestionar la agenda de otro médico.');
        }
    }

    private function assertSessionScope(MedicalSession $session): void
    {
        $this->assertDoctorScope((int) $session->doctor_id);
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

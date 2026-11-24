<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\MedicalSessionStatus;
use App\Events\MedicalSessionCancelled;
use App\Models\BlockedDate;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\SessionBreak;
use App\Models\Specialty;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MedicalSessionService
{
    public function __construct(
        private readonly ConcurrencyLock $lock,
        private readonly AppointmentStatusMachine $statusMachine,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): MedicalSession
    {
        $doctorId = (int) $data['doctor_id'];
        $date = $data['date'];

        return $this->lock->run("session:{$doctorId}:{$date}", function () use ($data, $doctorId, $date) {
            $this->validateDoctor($doctorId);
            $this->validateTimes($data['start_time'], $data['end_time']);
            $this->validateSpecialty($data['specialty_id'] ?? null);
            $this->validateBlockedDate($doctorId, $date);
            $this->validateNoOverlap($doctorId, $date, $data['start_time'], $data['end_time']);
            $this->validateBreaks($data['start_time'], $data['end_time'], $data['breaks'] ?? []);

            return DB::transaction(function () use ($data) {
                $breaks = $data['breaks'] ?? [];
                unset($data['breaks']);
                $data['status'] ??= MedicalSessionStatus::Scheduled->value;
                $data['created_by'] = auth()->id();
                $session = MedicalSession::create($data);

                foreach ($breaks as $break) {
                    $session->breaks()->create($break);
                }

                return $session;
            });
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(MedicalSession $session, array $data): MedicalSession
    {
        $doctorId = (int) ($data['doctor_id'] ?? $session->doctor_id);
        $date = $data['date'] ?? $session->date->toDateString();
        $start = $data['start_time'] ?? $session->start_time;
        $end = $data['end_time'] ?? $session->end_time;

        return $this->lock->run("session:{$doctorId}:{$date}", function () use ($session, $data, $doctorId, $date, $start, $end) {
            $this->validateDoctor($doctorId);
            $this->validateTimes($start, $end);
            $this->validateSpecialty($data['specialty_id'] ?? $session->specialty_id);
            $this->validateBlockedDate($doctorId, $date, $session->id);
            $this->validateNoOverlap($doctorId, $date, $start, $end, $session->id);
            $breaks = $data['breaks'] ?? $session->breaks->toArray();
            $this->validateBreaks($start, $end, $breaks);

            unset($data['breaks']);

            DB::transaction(function () use ($session, $data, $breaks, $date, $start, $end) {
                $session->update($data);

                $session->appointments()
                    ->active()
                    ->notCancelled()
                    ->where(function ($query) use ($date, $start, $end) {
                        $query->whereDate('date', '!=', $date)
                            ->orWhere('starttime', '<', $start)
                            ->orWhere('endtime', '>', $end);
                    })
                    ->when(true, fn ($query) => $query->whereNotIn('status', [
                        AppointmentStatus::Completed->value,
                        AppointmentStatus::NoShow->value,
                    ]))
                    ->lockForUpdate()
                    ->each(function ($appointment) {
                        throw ValidationException::withMessages(['start_time' => 'Existen citas activas fuera del nuevo horario; reprográmalas antes de modificar la sesión.']);
                    });

                $session->breaks()->delete();
                foreach ($breaks as $break) {
                    $session->breaks()->create([
                        'start_time' => $break['start_time'],
                        'end_time' => $break['end_time'],
                        'reason' => $break['reason'] ?? null,
                    ]);
                }
            });

            return $session->fresh()->load('breaks');
        });
    }

    public function cancel(MedicalSession $session, ?string $reason = null): MedicalSession
    {
        return $this->lock->run("session:{$session->doctor_id}:{$session->date->toDateString()}", function () use ($session, $reason) {
            DB::transaction(function () use ($session, $reason) {
                $session->update(['status' => MedicalSessionStatus::Cancelled]);

                $session->appointments()
                    ->active()
                    ->whereIn('status', [
                        AppointmentStatus::Pending->value,
                        AppointmentStatus::Confirmed->value,
                        AppointmentStatus::Waiting->value,
                        AppointmentStatus::InConsultation->value,
                    ])
                    ->lockForUpdate()
                    ->each(function ($appointment) use ($reason) {
                        $from = $appointment->status;
                        $appointment->update([
                            'status' => AppointmentStatus::Cancelled,
                            'active' => false,
                            'cancelled_at' => now(),
                        ]);
                        $appointment->histories()->create([
                            'from_status' => $from instanceof AppointmentStatus ? $from->value : $from,
                            'to_status' => AppointmentStatus::Cancelled->value,
                            'actor_id' => auth()->id(),
                            'reason' => $reason ?: 'Sesión médica cancelada.',
                        ]);
                    });
            });

            MedicalSessionCancelled::dispatch($session->fresh(), $reason);

            return $session->fresh();
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function blockDate(array $data): BlockedDate
    {
        return BlockedDate::updateOrCreate(
            ['doctor_id' => $data['doctor_id'], 'date' => $data['date']],
            ['reason' => $data['reason'] ?? null]
        );
    }

    public function unblockDate(int $doctorId, string $date): bool
    {
        return (bool) BlockedDate::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->delete();
    }

    private function validateDoctor(int $doctorId): void
    {
        $doctor = User::find($doctorId);

        if (! $doctor || ! $doctor->hasRole('doctor')) {
            throw ValidationException::withMessages(['doctor_id' => 'El médico seleccionado no es válido.']);
        }

        if (Doctor::query()->where('user_id', $doctorId)->where('active', false)->exists()) {
            throw ValidationException::withMessages(['doctor_id' => 'El médico seleccionado está inactivo.']);
        }
    }

    private function validateSpecialty(mixed $specialtyId): void
    {
        if ($specialtyId && ! Specialty::query()->whereKey($specialtyId)->where('active', true)->exists()) {
            throw ValidationException::withMessages(['specialty_id' => 'La especialidad seleccionada no está activa.']);
        }
    }

    private function validateBlockedDate(int $doctorId, string $date, ?int $ignoreSessionId = null): void
    {
        if (BlockedDate::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->exists()) {
            throw ValidationException::withMessages(['date' => 'La fecha está bloqueada para este médico.']);
        }
    }

    private function validateTimes(string $start, string $end): void
    {
        if (strcmp($end, $start) <= 0) {
            throw ValidationException::withMessages(['end_time' => 'La hora de fin debe ser posterior a la de inicio.']);
        }
    }

    private function validateNoOverlap(int $doctorId, string $date, string $start, string $end, ?int $ignoreId = null): void
    {
        $overlap = MedicalSession::query()
            ->forDoctor($doctorId)
            ->onDate($date)
            ->where('status', '!=', MedicalSessionStatus::Cancelled->value)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['start_time' => 'El médico ya tiene una sesión solapada en esa fecha.']);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $breaks
     */
    private function validateBreaks(string $start, string $end, array $breaks): void
    {
        $previousEnd = $start;

        foreach ($breaks as $break) {
            $breakStart = $break['start_time'];
            $breakEnd = $break['end_time'];

            if (strcmp($breakEnd, $breakStart) <= 0
                || strcmp($breakStart, $start) < 0
                || strcmp($breakEnd, $end) > 0
                || strcmp($breakStart, $previousEnd) < 0) {
                throw ValidationException::withMessages(['breaks' => 'Los descansos deben estar dentro de la sesión y no solaparse.']);
            }

            $previousEnd = $breakEnd;
        }
    }
}

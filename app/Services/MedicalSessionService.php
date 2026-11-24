<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\MedicalSessionStatus;
use App\Events\AppointmentCancelled;
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
            $this->validateBusinessHours($date, $data['start_time'], $data['end_time']);
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
        $originalDoctorId = (int) $session->doctor_id;
        $originalDate = $session->date->toDateString();
        $doctorId = (int) ($data['doctor_id'] ?? $originalDoctorId);
        $date = $data['date'] ?? $originalDate;
        $start = $data['start_time'] ?? $session->start_time;
        $end = $data['end_time'] ?? $session->end_time;

        $keys = ["session:{$doctorId}:{$date}"];
        if ($doctorId !== $originalDoctorId || $date !== $originalDate) {
            $keys[] = "session:{$originalDoctorId}:{$originalDate}";
        }
        sort($keys);

        return $this->lock->runMany($keys, function () use (
            $session,
            $data,
            $doctorId,
            $originalDoctorId,
            $date,
            $start,
            $end
        ) {
            $this->validateDoctor($doctorId);
            $this->validateBusinessHours($date, $start, $end);
            $this->validateTimes($start, $end);
            $this->validateSpecialty($data['specialty_id'] ?? $session->specialty_id);
            $this->validateBlockedDate($doctorId, $date, $session->id);
            $this->validateNoOverlap($doctorId, $date, $start, $end, $session->id);
            $breaks = $data['breaks'] ?? $session->breaks->toArray();
            $this->validateBreaks($start, $end, $breaks, $session);

            unset($data['breaks']);

            DB::transaction(function () use (
                $session,
                $data,
                $doctorId,
                $originalDoctorId,
                $breaks,
                $date,
                $start,
                $end
            ) {
                $session->update($data);

                if ($doctorId !== $originalDoctorId) {
                    $session->appointments()
                        ->where('doctor_id', $originalDoctorId)
                        ->lockForUpdate()
                        ->update(['doctor_id' => $doctorId]);
                }

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
        if ($session->status instanceof MedicalSessionStatus
            && $session->status === MedicalSessionStatus::Cancelled) {
            return $session;
        }

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

                        $this->statusMachine::assertTransition(
                            $appointment,
                            AppointmentStatus::Cancelled
                        );

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

                        AppointmentCancelled::dispatch($appointment->fresh(), $reason);
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
        $doctorId = (int) $data['doctor_id'];
        $date = $data['date'];

        return $this->lock->run("session:{$doctorId}:{$date}", function () use ($data, $doctorId, $date) {
            return DB::transaction(fn () => BlockedDate::updateOrCreate(
                ['doctor_id' => $doctorId, 'date' => $date],
                ['reason' => $data['reason'] ?? null]
            ));
        });
    }

    public function unblockDate(int $doctorId, string $date): bool
    {
        return (bool) BlockedDate::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->delete();
    }

    private function validateBusinessHours(string $date, string $start, string $end): void
    {
        $weekday = Carbon::parse($date)->dayOfWeek;
        $config = config('appointments');

        if (! in_array($weekday, $config['weekdays'], true)) {
            throw ValidationException::withMessages(['date' => 'Las sesiones médicas solo pueden programarse de lunes a viernes.']);
        }

        if (strcmp($start, $config['start_time']) < 0 || strcmp($end, $config['end_time']) > 0) {
            throw ValidationException::withMessages([
                'start_time' => "Las sesiones médicas deben estar entre {$config['start_time']} y {$config['end_time']}.",
            ]);
        }
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
    private function validateBreaks(
        string $start,
        string $end,
        array $breaks,
        ?MedicalSession $session = null
    ): void {
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

            if ($session) {
                $conflict = $session->appointments()
                    ->active()
                    ->notTerminal()
                    ->where('starttime', '<', $breakEnd)
                    ->where('endtime', '>', $breakStart)
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'breaks' => 'No se puede registrar un descanso sobre citas ya reservadas.',
                    ]);
                }
            }

            $previousEnd = $breakEnd;
        }
    }
}

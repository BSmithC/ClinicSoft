<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentAvailabilityService
{
    /**
     * @return array<int, string> Empty when the slot is available.
     */
    public function validate(
        int $doctorId,
        string $date,
        string $start,
        string $end,
        ?int $ignoreId = null
    ): array {
        $errors = [];
        $config = config('appointments');
        $startMinutes = $this->toMinutes($start);
        $endMinutes = $this->toMinutes($end);
        $workStart = $this->toMinutes($config['start_time']);
        $workEnd = $this->toMinutes($config['end_time']);

        if ($endMinutes <= $startMinutes) {
            $errors[] = 'La hora de fin debe ser posterior a la de inicio.';
        }

        if (! in_array(Carbon::parse($date)->dayOfWeekIso, $config['weekdays'], true)) {
            $errors[] = 'Solo se pueden agendar citas de lunes a viernes.';
        }

        if ($startMinutes < $workStart || $endMinutes > $workEnd) {
            $errors[] = "El horario permitido es de {$config['start_time']} a {$config['end_time']}.";
        }

        $duration = $endMinutes - $startMinutes;
        if ($duration < $config['min_duration_minutes'] || $duration > $config['max_duration_minutes']) {
            $errors[] = 'La duración de la cita está fuera de los límites permitidos.';
        }

        if ($errors !== []) {
            return $errors;
        }

        $conflict = Appointment::query()
            ->active()
            ->notCancelled()
            ->forDoctor($doctorId)
            ->onDate($date)
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->first();

        if ($conflict) {
            $errors[] = 'El médico ya tiene una cita que se solapa en ese horario.';
        }

        return $errors;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);

        return ((int) $hours * 60) + (int) $minutes;
    }
}

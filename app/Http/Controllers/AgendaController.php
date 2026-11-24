<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\Specialty;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AgendaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|doctor']);
    }

    public function index(Request $request, AvailabilityService $availability)
    {
        $validated = $request->validate([
            'view' => ['sometimes', Rule::in(['month', 'week', 'day'])],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d'],
            'doctor_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'specialty_id' => ['sometimes', 'nullable', 'exists:specialties,id'],
            'status' => ['sometimes', 'nullable', Rule::enum(AppointmentStatus::class)],
            'patient' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $view = $validated['view'] ?? 'month';
        $focusDate = $validated['date'] ?? $validated['from'] ?? now()->toDateString();
        [$from, $to] = $this->resolveRange($view, $focusDate, $validated);

        $isAdmin = Auth::user()->isAdmin();
        $doctorId = $isAdmin
            ? ($validated['doctor_id'] ?? null)
            : Auth::id();
        $specialtyId = $validated['specialty_id'] ?? null;
        $status = $validated['status'] ?? null;
        $patientSearch = $validated['patient'] ?? null;

        $sessions = MedicalSession::query()
            ->with('doctor:id,name,last_name', 'specialty:id,name', 'appointments.patient:id,name,last_name')
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->when($specialtyId, fn ($query) => $query->where('specialty_id', $specialtyId))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $appointments = Appointment::query()
            ->with('patient:id,name,last_name', 'doctor:id,name,last_name', 'session:id,title')
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->when($specialtyId, fn ($query) => $query->where('specialty_id', $specialtyId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($patientSearch, fn ($query) => $query->whereHas('patient', fn ($patientQuery) => $patientQuery
                ->where('name', 'like', "%{$patientSearch}%")
                ->orWhere('last_name', 'like', "%{$patientSearch}%")
                ->orWhere('DNI', 'like', "%{$patientSearch}%")))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('starttime')
            ->get();

        $availabilitySlots = [];
        foreach ($sessions as $session) {
            $availabilitySlots[$session->id] = $availability->slotsForSession($session);
        }

        $blockedDates = BlockedDate::query()
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get(['id', 'doctor_id', 'date', 'reason']);

        $events = $this->buildEvents($appointments, $sessions, $blockedDates);
        $daySummary = $this->buildDaySummary($from, $to, $appointments, $sessions, $blockedDates, $availabilitySlots);

        return Inertia::render('Agenda/index', [
            // New calendar contract.
            'events' => $events,
            'daySummary' => $daySummary,
            'timezone' => config('app.timezone'),
            'availability' => config('appointments'),
            'patients' => Patient::query()->get(['id', 'name', 'last_name', 'DNI']),
            'specialties' => Specialty::query()->active()->get(['id', 'name']),

            // Existing contract kept for compatibility with the rest of the system.
            'sessions' => $sessions,
            'appointments' => $appointments,
            'availabilitySlots' => $availabilitySlots,
            'blockedDates' => $blockedDates,
            'statuses' => AppointmentStatus::options(),
            'doctors' => Doctor::query()->with('user:id,name,last_name', 'specialties:id,name')->get(),
            'filters' => [
                'view' => $view,
                'date' => $focusDate,
                'from' => $from,
                'to' => $to,
                'doctor_id' => $doctorId,
                'specialty_id' => $specialtyId,
                'status' => $status,
                'patient' => $patientSearch,
            ],
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{0: string, 1: string}
     */
    private function resolveRange(string $view, string $focusDate, array $validated): array
    {
        if (isset($validated['from'], $validated['to']) && $validated['to'] >= $validated['from']) {
            $from = $validated['from'];
            $to = $validated['to'];
        } else {
            $date = Carbon::parse($focusDate);

            [$from, $to] = match ($view) {
                'day' => [$date->toDateString(), $date->toDateString()],
                'week' => [$date->copy()->startOfWeek()->toDateString(), $date->copy()->endOfWeek()->toDateString()],
                default => [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()],
            };
        }

        $start = Carbon::parse($from);
        $end = Carbon::parse($to);

        // Keep the server-side calculation bounded even if a client sends a huge range.
        if ($start->diffInDays($end) > 92) {
            $end = $start->copy()->addDays(92);
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Appointment>  $appointments
     * @param  \Illuminate\Database\Eloquent\Collection<int, MedicalSession>  $sessions
     * @param  \Illuminate\Database\Eloquent\Collection<int, BlockedDate>  $blockedDates
     * @return array<int, array<string, mixed>>
     */
    private function buildEvents($appointments, $sessions, $blockedDates): array
    {
        $events = [];

        foreach ($appointments as $appointment) {
            $status = $appointment->status;
            $events[] = [
                'id' => 'appointment-' . $appointment->id,
                'type' => 'appointment',
                'sourceId' => $appointment->id,
                'date' => $this->dateKey($appointment->date),
                'start' => $this->timeKey($appointment->starttime),
                'end' => $this->timeKey($appointment->endtime),
                'title' => $this->personName($appointment->patient),
                'subtitle' => 'Dr. ' . $this->personName($appointment->doctor),
                'status' => $status instanceof AppointmentStatus ? $status->value : (string) $status,
                'statusLabel' => $status instanceof AppointmentStatus ? $status->label() : (string) $status,
                'specialtyId' => $appointment->specialty_id,
                'specialtyName' => $appointment->specialty?->name,
                'doctorId' => $appointment->doctor_id,
                'patientId' => $appointment->patient_id,
                'patientSearch' => $this->personName($appointment->patient),
                'sessionId' => $appointment->session_id,
                'sessionTitle' => $appointment->session?->title,
                'notes' => $appointment->notes,
                'reason' => null,
                'can' => [
                    'edit' => Auth::user()->can('update', $appointment) && ! $status->isTerminal(),
                    'cancel' => Auth::user()->can('delete', $appointment) && ! $status->isTerminal(),
                    'viewHistory' => Auth::user()->can('view', $appointment),
                    'changeStatus' => Auth::user()->can('update', $appointment),
                ],
                'transitions' => array_map(
                    fn (AppointmentStatus $item) => ['value' => $item->value, 'label' => $item->label()],
                    $status->allowedTransitions()
                ),
            ];
        }

        foreach ($sessions as $session) {
            $sessionStatus = $session->status;
            $events[] = [
                'id' => 'session-' . $session->id,
                'type' => 'session',
                'sourceId' => $session->id,
                'date' => $this->dateKey($session->date),
                'start' => $this->timeKey($session->start_time),
                'end' => $this->timeKey($session->end_time),
                'title' => $session->title,
                'subtitle' => 'Dr. ' . $this->personName($session->doctor),
                'status' => $sessionStatus instanceof \App\Enums\MedicalSessionStatus ? $sessionStatus->value : (string) $sessionStatus,
                'statusLabel' => $sessionStatus instanceof \App\Enums\MedicalSessionStatus ? $sessionStatus->label() : (string) $sessionStatus,
                'specialtyId' => $session->specialty_id,
                'specialtyName' => $session->specialty?->name,
                'doctorId' => $session->doctor_id,
                'patientId' => null,
                'patientSearch' => '',
                'sessionId' => $session->id,
                'sessionTitle' => $session->title,
                'notes' => $session->notes,
                'reason' => null,
                'can' => [
                    'edit' => Auth::user()->can('update', $session),
                    'cancel' => Auth::user()->can('update', $session),
                    'viewHistory' => false,
                    'changeStatus' => false,
                ],
                'transitions' => [],
            ];
        }

        foreach ($blockedDates as $blocked) {
            $events[] = [
                'id' => 'block-' . $blocked->id,
                'type' => 'block',
                'sourceId' => $blocked->id,
                'date' => $this->dateKey($blocked->date),
                'start' => null,
                'end' => null,
                'title' => 'Fecha bloqueada',
                'subtitle' => $blocked->reason ?: 'Sin motivo registrado',
                'status' => 'blocked',
                'statusLabel' => 'Bloqueada',
                'specialtyId' => null,
                'specialtyName' => null,
                'doctorId' => $blocked->doctor_id,
                'patientId' => null,
                'patientSearch' => '',
                'sessionId' => null,
                'sessionTitle' => null,
                'notes' => null,
                'reason' => $blocked->reason,
                'can' => [
                    'edit' => false,
                    'cancel' => false,
                    'viewHistory' => false,
                    'changeStatus' => false,
                ],
                'transitions' => [],
            ];
        }

        return $events;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Appointment>  $appointments
     * @param  \Illuminate\Database\Eloquent\Collection<int, MedicalSession>  $sessions
     * @param  \Illuminate\Database\Eloquent\Collection<int, BlockedDate>  $blockedDates
     * @param  array<int, array<int, array<string, mixed>>>  $availabilitySlots
     * @return array<string, array<string, int>>
     */
    private function buildDaySummary(
        string $from,
        string $to,
        $appointments,
        $sessions,
        $blockedDates,
        array $availabilitySlots
    ): array {
        $summary = [];
        $cursor = Carbon::parse($from);
        $end = Carbon::parse($to);

        while ($cursor <= $end) {
            $date = $cursor->toDateString();
            $sessionsForDay = $sessions->filter(fn ($session) => $this->dateKey($session->date) === $date);
            $free = 0;

            foreach ($sessionsForDay as $session) {
                $free += collect($availabilitySlots[$session->id] ?? [])
                    ->filter(fn ($slot) => $slot['available'])
                    ->count();
            }

            $summary[$date] = [
                'appointments' => $appointments->filter(fn ($item) => $this->dateKey($item->date) === $date)->count(),
                'sessions' => $sessionsForDay->count(),
                'free' => $free,
                'blocked' => $blockedDates->filter(fn ($item) => $this->dateKey($item->date) === $date)->count(),
            ];

            $cursor->addDay();
        }

        return $summary;
    }

    private function dateKey(mixed $date): string
    {
        return $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : substr((string) $date, 0, 10);
    }

    private function timeKey(mixed $time): string
    {
        $value = (string) $time;

        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    private function personName(mixed $person): string
    {
        if (! $person) {
            return '';
        }

        return trim(($person->name ?? '') . ' ' . ($person->last_name ?? ''));
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\ChangeAppointmentStatusRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Drug;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AppointmentBookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Appointment::class, 'appointment');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $lastDays = $request->input('lastDays', '30');
        $query = Appointment::query()->select('appointments.*');
        $this->scopeToDoctor($query);

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('appointments.title', 'like', "%{$search}%")
                    ->orWhere('appointments.date', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn (Builder $patient) => $patient
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        if ($lastDays && $lastDays !== 'all') {
            if (is_numeric($lastDays)) {
                $query->where('appointments.created_at', '>=', Carbon::now()->subDays((int) $lastDays)->startOfDay());
            } elseif ($lastDays === 'month') {
                $query->where('appointments.created_at', '>=', Carbon::now()->startOfMonth());
            } elseif ($lastDays === 'year') {
                $query->where('appointments.created_at', '>=', Carbon::now()->startOfYear());
            }
        }

        return Inertia::render('Appointment/index', [
            'appointments' => $query->with('patient', 'doctor', 'drug', 'session', 'specialty')
                ->orderByDesc('date')
                ->orderBy('starttime')
                ->paginate(10)
                ->withQueryString(),
            'filters' => ['search' => $search, 'lastDays' => $lastDays],
            'statuses' => AppointmentStatus::options(),
        ]);
    }

    public function create()
    {
        $doctorQuery = User::query()->whereHas('roles', fn (Builder $q) => $q->where('name', 'doctor'));

        if (! Auth::user()->isAdmin()) {
            $doctorQuery->whereKey(Auth::id());
        }

        return Inertia::render('Appointment/create', [
            'patients' => Patient::query()->get(['id', 'name', 'last_name']),
            'doctors' => $doctorQuery->get(['id', 'name', 'last_name']),
            'doctorProfiles' => \App\Models\Doctor::query()->with('user:id,name,last_name')->get(),
            'specialties' => Specialty::query()->active()->get(['id', 'name', 'slug']),
            'sessions' => $this->sessionsForCurrentUser(),
            'drugs' => Drug::query()->get(['id', 'name']),
            'statuses' => AppointmentStatus::options(),
            'availability' => config('appointments'),
        ]);
    }

    public function store(StoreAppointmentRequest $request, AppointmentBookingService $booking)
    {
        $data = $request->validated();
        $this->enforceDoctorScope($data);

        $appointment = $booking->book($data);

        return redirect()->route('appointments.index')
            ->with('success', "Cita #{$appointment->id} creada correctamente.");
    }

    public function show(Appointment $appointment)
    {
        return Inertia::render('Appointment/show', [
            'appointment' => $appointment->loadMissing('patient', 'doctor', 'drug', 'session.specialty', 'specialty', 'histories.actor'),
            'statuses' => AppointmentStatus::options(),
            'transitions' => array_map(
                fn (AppointmentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                $appointment->status->allowedTransitions()
            ),
        ]);
    }

    public function edit(Appointment $appointment)
    {
        return Inertia::render('Appointment/edit', [
            'appointments' => $appointment,
            'patients' => Patient::query()->get(['id', 'name', 'last_name']),
            'doctors' => User::query()->whereHas('roles', fn (Builder $q) => $q->where('name', 'doctor'))
                ->get(['id', 'name', 'last_name']),
            'specialties' => Specialty::query()->active()->get(['id', 'name', 'slug']),
            'sessions' => $this->sessionsForCurrentUser(),
            'drugs' => Drug::query()->get(['id', 'name']),
            'statuses' => AppointmentStatus::options(),
            'transitions' => array_map(
                fn (AppointmentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                $appointment->status->allowedTransitions()
            ),
            'availability' => config('appointments'),
        ]);
    }

    public function update(
        UpdateAppointmentRequest $request,
        Appointment $appointment,
        AppointmentBookingService $booking
    ) {
        $data = $request->validated();
        $this->enforceDoctorScope($data);

        if (isset($data['status'])) {
            $status = AppointmentStatus::from($data['status']);

            if ($status !== $appointment->status) {
                if ($status === AppointmentStatus::Cancelled) {
                    $booking->cancel($appointment, $data['reason'] ?? null);
                } else {
                    $booking->changeStatus($appointment, $status, $data['reason'] ?? null);
                }
            }

            unset($data['status']);
        }

        $booking->reschedule($appointment->fresh(), $data);

        return redirect()->route('appointments.index')->with('success', 'Cita actualizada correctamente.');
    }

    public function changeStatus(
        ChangeAppointmentStatusRequest $request,
        Appointment $appointment,
        AppointmentBookingService $booking
    ) {
        $this->authorize('update', $appointment);

        $status = AppointmentStatus::from($request->validated('status'));

        if ($status === AppointmentStatus::Cancelled) {
            $booking->cancel($appointment, $request->validated('reason'));
        } else {
            $booking->changeStatus($appointment, $status, $request->validated('reason'));
        }

        return back()->with('success', 'Estado de la cita actualizado.');
    }

    public function destroy(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        AppointmentBookingService $booking
    ) {
        $booking->cancel($appointment, $request->validated('reason'));

        return redirect()->route('appointments.index')->with('success', 'Cita cancelada. El espacio fue liberado.');
    }

    public function history(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        return Inertia::render('Appointment/history', [
            'appointment' => $appointment->loadMissing('patient:id,name,last_name', 'doctor:id,name,last_name'),
            'histories' => $appointment->histories()->with('actor:id,name')->get(),
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Appointment>  $query
     */
    private function scopeToDoctor(Builder $query): void
    {
        if (! Auth::user()->isAdmin()) {
            $query->where('doctor_id', Auth::id());
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function enforceDoctorScope(array &$data): void
    {
        if (! Auth::user()->isAdmin()) {
            $data['doctor_id'] = Auth::id();
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, MedicalSession>
     */
    private function sessionsForCurrentUser()
    {
        $query = MedicalSession::query()->with('specialty:id,name')->orderByDesc('date');

        if (! Auth::user()->isAdmin()) {
            $query->where('doctor_id', Auth::id());
        }

        return $query->limit(100)->get();
    }
}

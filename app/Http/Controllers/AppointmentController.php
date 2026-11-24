<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentAvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $query = Appointment::query()->select('appointments.*');
        $lastDays = $request->input('lastDays', '30');

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereRaw('appointments.title LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('appointments.doctor_id LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('appointments.patient_id LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('appointments.date LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('appointments.attended LIKE ?', ['%' . $search . '%']);
            });
        }

        if ($lastDays && $lastDays !== 'all') {
            if (is_numeric($lastDays)) {
                $dateFrom = Carbon::now()->subDays((int) $lastDays)->startOfDay();
                $query->where('created_at', '>=', $dateFrom);
            } elseif ($lastDays === 'month') {
                $query->where('created_at', '>=', Carbon::now()->startOfMonth());
            } elseif ($lastDays === 'year') {
                $query->where('created_at', '>=', Carbon::now()->startOfYear());
            }
        }

        return Inertia::render('Appointment/index', [
            'appointments' => $query->with('patient', 'doctor', 'drug')->paginate(10),
            'filters' => ['search' => $search, 'lastDays' => $lastDays],
            'statuses' => AppointmentStatus::options(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Appointment/create', [
            'patients' => Patient::query()->get(['id', 'name', 'last_name']),
            'doctors' => User::query()->get(['id', 'name', 'last_name']),
            'drugs' => Drug::query()->get(['id', 'name']),
            'statuses' => AppointmentStatus::options(),
            'availability' => config('appointments'),
        ]);
    }

    public function store(Request $request, AppointmentAvailabilityService $availability)
    {
        $validated = $this->validateAppointment($request);

        $errors = $availability->validate(
            (int) $validated['doctor_id'],
            $validated['date'],
            $validated['starttime'],
            $validated['endtime'],
        );

        if ($errors !== []) {
            throw ValidationException::withMessages(['starttime' => $errors]);
        }

        $status = isset($validated['status'])
            ? AppointmentStatus::from($validated['status'])
            : AppointmentStatus::Pending;

        DB::transaction(fn () => Appointment::create([
            'title' => $validated['title'],
            'doctor_id' => $validated['doctor_id'],
            'patient_id' => $validated['patient_id'],
            'drug_id' => $validated['drug_id'] ?? null,
            'status' => $status,
            'attended' => ($validated['attended'] ?? false) || $status === AppointmentStatus::Completed,
            'date' => $validated['date'],
            'starttime' => $validated['starttime'],
            'endtime' => $validated['endtime'],
            'active' => $validated['active'] ?? true,
        ]));

        return redirect()->route('appointments.index')->with('success', 'Appointment created successfully.');
    }

    public function show(Appointment $appointment)
    {
        return Inertia::render('Appointment/show', [
            'appointment' => $appointment->loadMissing('patient', 'doctor', 'drug'),
            'statuses' => AppointmentStatus::options(),
        ]);
    }

    public function edit(Appointment $appointment)
    {
        return Inertia::render('Appointment/edit', [
            'appointments' => $appointment,
            'patients' => Patient::query()->get(['id', 'name', 'last_name']),
            'doctors' => User::query()->get(['id', 'name', 'last_name']),
            'drugs' => Drug::query()->get(['id', 'name']),
            'statuses' => AppointmentStatus::options(),
            'availability' => config('appointments'),
        ]);
    }

    public function update(Request $request, Appointment $appointment, AppointmentAvailabilityService $availability)
    {
        $validated = $this->validateAppointment($request);

        $errors = $availability->validate(
            (int) $validated['doctor_id'],
            $validated['date'],
            $validated['starttime'],
            $validated['endtime'],
            $appointment->id,
        );

        if ($errors !== []) {
            throw ValidationException::withMessages(['starttime' => $errors]);
        }

        $status = isset($validated['status'])
            ? AppointmentStatus::from($validated['status'])
            : $appointment->status;

        DB::transaction(fn () => $appointment->update([
            'title' => $validated['title'],
            'doctor_id' => $validated['doctor_id'],
            'patient_id' => $validated['patient_id'],
            'drug_id' => $validated['drug_id'] ?? null,
            'status' => $status,
            'attended' => ($validated['attended'] ?? false) || $status === AppointmentStatus::Completed,
            'date' => $validated['date'],
            'starttime' => $validated['starttime'],
            'endtime' => $validated['endtime'],
            'active' => $validated['active'] ?? true,
        ]));

        return redirect()->route('appointments.index')->with('success', 'Appointment updated successfully.');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'active' => false,
        ]);

        return redirect()->route('appointments.index')->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAppointment(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'doctor_id' => ['required', 'exists:users,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'drug_id' => ['nullable', 'exists:drugs,id'],
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
            'attended' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'date' => ['required', 'date_format:Y-m-d'],
            'starttime' => ['required', 'date_format:H:i', 'before:endtime'],
            'endtime' => ['required', 'date_format:H:i', 'after:starttime'],
        ]);

        // Store times in a single comparable format (H:i:s).
        $validated['starttime'] .= ':00';
        $validated['endtime'] .= ':00';

        return $validated;
    }
}

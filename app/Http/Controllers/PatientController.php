<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Patient::class, 'patient');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $lastDays = $request->input('lastDays', '30');
        $query = Patient::query()->select('patients.*');

        if (! Auth::user()->isAdmin()) {
            $query->whereHas('Appointment', fn (Builder $q) => $q->where('doctor_id', Auth::id()));
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('patients.name', 'like', "%{$search}%")
                    ->orWhere('patients.DNI', 'like', "%{$search}%")
                    ->orWhere('patients.last_name', 'like', "%{$search}%")
                    ->orWhere('patients.email', 'like', "%{$search}%")
                    ->orWhere('patients.date_of_birth', 'like', "%{$search}%");
            });
        }

        if ($lastDays && $lastDays !== 'all') {
            if (is_numeric($lastDays)) {
                $query->where('patients.created_at', '>=', Carbon::now()->subDays((int) $lastDays)->startOfDay());
            } elseif ($lastDays === 'month') {
                $query->where('patients.created_at', '>=', Carbon::now()->startOfMonth());
            } elseif ($lastDays === 'year') {
                $query->where('patients.created_at', '>=', Carbon::now()->startOfYear());
            }
        }

        return Inertia::render('Patient/index', [
            'patients' => $query->withCount('Appointment')
                ->orderBy('patients.id')
                ->paginate(10)
                ->withQueryString(),
            'filters' => ['search' => $search, 'lastDays' => $lastDays],
        ]);
    }

    public function create()
    {
        return Inertia::render('Patient/create');
    }

    public function store(StorePatientRequest $request)
    {
        Patient::create($request->validated());

        return redirect()->route('patients.index')->with('success', 'Paciente creado correctamente.');
    }

    public function show(Patient $patient)
    {
        return Inertia::render('Patient/show', [
            'patients' => $patient,
            'history' => $patient->Appointment()
                ->with('doctor:id,name,last_name', 'session:id,title')
                ->with('histories')
                ->orderByDesc('date')
                ->get(),
        ]);
    }

    public function edit(Patient $patient)
    {
        return Inertia::render('Patient/edit', ['patients' => $patient]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient->update($request->validated());

        return redirect()->route('patients.index')->with('success', 'Paciente actualizado correctamente.');
    }

    public function destroy(Patient $patient)
    {
        // Deactivation keeps every clinical record and appointment in the database.
        $patient->update(['status' => false]);

        return redirect()->route('patients.index')
            ->with('success', 'Paciente desactivado. Su historial se conserva íntegro.');
    }

    public function history(Patient $patient)
    {
        $this->authorize('view', $patient);

        return Inertia::render('Patient/history', [
            'patient' => $patient->only('id', 'name', 'last_name', 'DNI'),
            'appointments' => $patient->Appointment()
                ->with('doctor:id,name,last_name', 'session:id,title', 'histories.actor:id,name')
                ->orderByDesc('date')
                ->get(),
            'medicalHistories' => $patient->medicalHistories()->orderByDesc('created_at')->get(),
        ]);
    }
}

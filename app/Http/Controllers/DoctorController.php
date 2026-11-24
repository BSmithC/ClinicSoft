<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Doctor::class, 'doctor');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Doctor::query()->with('user:id,name,last_name,email', 'specialties:id,name');

        if (! Auth::user()->isAdmin()) {
            $query->whereKey(Auth::user()->doctorProfile?->id ?? 0);
        }

        if ($search) {
            $query->whereHas('user', fn (Builder $q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('active')) {
            $query->where('active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
        }

        return Inertia::render('Doctor/index', [
            'doctors' => $query->orderBy('id')->paginate(10)->withQueryString(),
            'filters' => ['search' => $search, 'active' => $request->input('active')],
        ]);
    }

    public function create()
    {
        return Inertia::render('Doctor/create', [
            'users' => User::query()
                ->whereHas('roles', fn (Builder $q) => $q->where('name', 'doctor'))
                ->whereDoesntHave('doctorProfile')
                ->get(['id', 'name', 'last_name', 'email']),
            'specialties' => Specialty::query()->active()->get(['id', 'name']),
        ]);
    }

    public function store(StoreDoctorRequest $request)
    {
        $data = $request->validated();
        $specialties = $data['specialties'] ?? [];
        unset($data['specialties']);

        DB::transaction(function () use ($data, $specialties) {
            $doctor = Doctor::create($data);
            $doctor->specialties()->sync($specialties);
        });

        return redirect()->route('doctors.index')->with('success', 'Perfil médico creado.');
    }

    public function show(Doctor $doctor)
    {
        return Inertia::render('Doctor/show', [
            'doctor' => $doctor->load('user:id,name,last_name,email', 'specialties:id,name,slug'),
            'sessions' => $doctor->sessions()->with('specialty:id,name')->limit(20)->get(),
            'appointments' => $doctor->appointments()
                ->with('patient:id,name,last_name')
                ->orderByDesc('date')
                ->limit(20)
                ->get(),
        ]);
    }

    public function edit(Doctor $doctor)
    {
        return Inertia::render('Doctor/edit', [
            'doctor' => $doctor->load('specialties:id'),
            'specialties' => Specialty::query()->active()->get(['id', 'name']),
        ]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $data = $request->validated();
        $specialties = $data['specialties'] ?? null;
        unset($data['specialties']);

        DB::transaction(function () use ($doctor, $data, $specialties) {
            $doctor->update($data);

            if ($specialties !== null) {
                $doctor->specialties()->sync($specialties);
            }
        });

        return redirect()->route('doctors.index')->with('success', 'Perfil médico actualizado.');
    }

    public function destroy(Doctor $doctor)
    {
        // Deactivate instead of deleting: clinical history must survive.
        $doctor->update(['active' => false]);

        return redirect()->route('doctors.index')->with('success', 'Médico desactivado. Su historial se conserva.');
    }

    public function agenda(Request $request, Doctor $doctor)
    {
        $this->authorize('view', $doctor);

        $from = $request->input('from', now()->toDateString());
        $to = $request->input('to', now()->addDays(6)->toDateString());

        return Inertia::render('Doctor/agenda', [
            'doctor' => $doctor->load('user:id,name,last_name', 'specialties:id,name'),
            'sessions' => MedicalSession::query()
                ->with('specialty:id,name', 'appointments.patient:id,name,last_name')
                ->where('doctor_id', $doctor->user_id)
                ->whereBetween('date', [$from, $to])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get(),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }
}

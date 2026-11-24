<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AgendaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|doctor']);
    }

    public function index(Request $request)
    {
        $from = $request->input('from', now()->toDateString());
        $to = $request->input('to', now()->addDays(6)->toDateString());
        $doctorId = Auth::user()->isAdmin()
            ? $request->input('doctor_id')
            : Auth::id();

        $sessions = MedicalSession::query()
            ->with('doctor:id,name,last_name', 'specialty:id,name', 'appointments.patient:id,name,last_name')
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $appointments = Appointment::query()
            ->with('patient:id,name,last_name', 'doctor:id,name,last_name', 'session:id,title')
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('starttime')
            ->get();

        return Inertia::render('Agenda/index', [
            'sessions' => $sessions,
            'appointments' => $appointments,
            'statuses' => AppointmentStatus::options(),
            'doctors' => Doctor::query()->with('user:id,name,last_name')->get(),
            'filters' => ['from' => $from, 'to' => $to, 'doctor_id' => $doctorId],
            'isAdmin' => Auth::user()->isAdmin(),
        ]);
    }
}

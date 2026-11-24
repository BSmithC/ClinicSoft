<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasRole('admin');
        $today = now()->toDateString();

        $appointments = Appointment::query();
        if (! $isAdmin) {
            $appointments->where('doctor_id', $userId);
        }

        $billing = Bill::query();
        if (! $isAdmin) {
            $billing->where('users_id', $userId);
        }

        return Inertia::render('Dashboard', [
            'Kpis' => [
                // Claves existentes (compatibilidad con el dashboard actual)
                'PatientsAll' => Patient::count(),
                'DrugsAll' => Drug::count(),
                'AppointmentsAll' => (clone $appointments)->count(),
                'TotalBilling' => (clone $billing)->sum('total'),

                // Nuevos KPIs por estado y ámbito
                'AppointmentsToday' => (clone $appointments)->onDate($today)->notCancelled()->count(),
                'PendingAppointments' => (clone $appointments)
                    ->where('status', AppointmentStatus::Pending->value)
                    ->count(),
                'CompletedAppointments' => (clone $appointments)
                    ->where('status', AppointmentStatus::Completed->value)
                    ->count(),
                'ActiveDoctors' => User::role('doctor')->count(),
            ],
        ]);
    }
}

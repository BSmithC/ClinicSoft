<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\MedicalSessionStatus;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();
        $today = now()->toDateString();

        $appointments = Appointment::query();
        if (! $isAdmin) {
            $appointments->where('doctor_id', $user->id);
        }

        $billing = Bill::query();
        if (! $isAdmin) {
            $billing->where('users_id', $user->id);
        }

        $sessions = MedicalSession::query();
        if (! $isAdmin) {
            $sessions->where('doctor_id', $user->id);
        }

        $patients = Patient::query();
        if (! $isAdmin) {
            $patients->whereHas('Appointment', fn (Builder $query) => $query->where('doctor_id', $user->id));
        }

        $activity = ActivityLog::query()->with('user:id,name')->orderByDesc('created_at');

        $statusCounts = [];
        foreach (AppointmentStatus::cases() as $status) {
            $statusCounts[$status->value] = (clone $appointments)->where('status', $status->value)->count();
        }

        $upcoming = (clone $appointments)
            ->active()
            ->notCancelled()
            ->where('date', '>=', $today)
            ->with('patient:id,name,last_name', 'doctor:id,name,last_name', 'session:id,title')
            ->orderBy('date')
            ->orderBy('starttime')
            ->limit(6)
            ->get();

        return Inertia::render('Dashboard', [
            'Kpis' => [
                // Claves históricas (compatibilidad con el dashboard anterior).
                'PatientsAll' => $patients->count(),
                'DrugsAll' => Drug::count(),
                'AppointmentsAll' => (clone $appointments)->count(),
                'TotalBilling' => (clone $billing)->sum('total'),

                // Nuevos indicadores reales calculados en MySQL.
                'AppointmentsToday' => (clone $appointments)->onDate($today)->notCancelled()->count(),
                'PendingAppointments' => $statusCounts[AppointmentStatus::Pending->value],
                'ConfirmedAppointments' => $statusCounts[AppointmentStatus::Confirmed->value],
                'CompletedAppointments' => $statusCounts[AppointmentStatus::Completed->value],
                'CancelledAppointments' => $statusCounts[AppointmentStatus::Cancelled->value],
                'NoShowAppointments' => $statusCounts[AppointmentStatus::NoShow->value],
                'ActiveDoctors' => User::role('doctor')->count(),
                'RegisteredDoctors' => Doctor::count(),
                'SpecialtiesAll' => Specialty::query()->active()->count(),
                'UpcomingAppointments' => (clone $appointments)
                    ->active()
                    ->notCancelled()
                    ->where('date', '>=', $today)
                    ->count(),
                'ActiveSessions' => (clone $sessions)
                    ->whereIn('status', [
                        MedicalSessionStatus::Scheduled->value,
                        MedicalSessionStatus::Active->value,
                    ])->count(),
                'BlockedDates' => \App\Models\BlockedDate::query()
                    ->when(! $isAdmin, fn ($query) => $query->where('doctor_id', $user->id))
                    ->count(),
            ],
            'statusBreakdown' => $statusCounts,
            'upcomingAppointments' => $upcoming,
            'recentActivity' => $activity->limit(8)->get(),
            'isAdmin' => $isAdmin,
        ]);
    }
}

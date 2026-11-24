<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\BlockedDate;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicalHistory;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\SessionBreak;
use App\Models\Specialty;
use App\Policies\AppointmentPolicy;
use App\Policies\DoctorPolicy;
use App\Policies\MedicalSessionPolicy;
use App\Policies\ModulePolicy;
use App\Policies\PatientPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\SpecialtyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        Bill::class => ModulePolicy::class,
        Doctor::class => DoctorPolicy::class,
        Drug::class => ModulePolicy::class,
        MedicalHistory::class => ModulePolicy::class,
        MedicalSession::class => MedicalSessionPolicy::class,
        Patient::class => PatientPolicy::class,
        BlockedDate::class => SchedulePolicy::class,
        SessionBreak::class => SchedulePolicy::class,
        Specialty::class => SpecialtyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

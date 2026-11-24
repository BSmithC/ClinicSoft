<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Drug;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Policies\ModulePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Appointment::class => ModulePolicy::class,
        Bill::class => ModulePolicy::class,
        Drug::class => ModulePolicy::class,
        MedicalHistory::class => ModulePolicy::class,
        Patient::class => ModulePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

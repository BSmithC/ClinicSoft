<?php

namespace App\Providers;

use App\Events\AppointmentBooked;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentStatusChanged;
use App\Events\MedicalSessionCancelled;
use App\Listeners\RecordClinicalActivity;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        AppointmentBooked::class => [RecordClinicalActivity::class],
        AppointmentCancelled::class => [RecordClinicalActivity::class],
        AppointmentStatusChanged::class => [RecordClinicalActivity::class],
        MedicalSessionCancelled::class => [RecordClinicalActivity::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

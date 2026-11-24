<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppointmentWelcomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('appointments.welcome'))->assertRedirect(route('login'));
    }

    public function test_user_without_module_role_is_forbidden(): void
    {
        $outsider = User::factory()->create();
        $outsider->assignRole('patient');

        $this->actingAs($outsider)->get(route('appointments.welcome'))->assertForbidden();
    }

    public function test_admin_sees_real_activity_and_can_filter_by_doctor(): void
    {
        $admin = $this->admin();
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $patient = Patient::factory()->create();
        $otherPatient = Patient::factory()->create();

        $this->appointment($doctor, $patient, $today, AppointmentStatus::Pending, '09:00');
        $this->appointment($doctor, $patient, $today, AppointmentStatus::Confirmed, '10:00');
        $this->appointment($doctor, $patient, $tomorrow, AppointmentStatus::Pending, '09:00');
        $this->appointment($otherDoctor, $otherPatient, $today, AppointmentStatus::Confirmed, '11:00');
        $this->appointment($doctor, $patient, $today, AppointmentStatus::Cancelled, '12:00');

        $this->sessionToday($doctor);

        $this->actingAs($admin)->get(route('appointments.welcome'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointment/Welcome')
                ->where('stats.today', 3)
                ->where('stats.pending', 2)
                ->where('stats.confirmed', 2)
                ->where('stats.availableDoctors', 1)
                ->has('upcomingAppointments', 4)
                ->has('doctors', 2)
                ->where('filters.doctor_id', null)
                ->where('isAdmin', true)
            );

        $this->actingAs($admin)->get(route('appointments.welcome', ['doctor_id' => $doctor->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointment/Welcome')
                ->where('stats.today', 2)
                ->where('stats.pending', 2)
                ->where('stats.confirmed', 1)
                ->where('stats.availableDoctors', 1)
                ->has('upcomingAppointments', 3)
                ->where('upcomingAppointments.0.doctor.id', $doctor->id)
                ->where('filters.doctor_id', $doctor->id)
            );
    }

    public function test_doctor_only_sees_own_agenda_even_when_requesting_another_doctor(): void
    {
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $today = now()->toDateString();

        $patient = Patient::factory()->create();
        $otherPatient = Patient::factory()->create();

        $this->appointment($doctor, $patient, $today, AppointmentStatus::Pending, '09:00');
        $this->appointment($otherDoctor, $otherPatient, $today, AppointmentStatus::Confirmed, '10:00');

        $this->sessionToday($doctor);
        $this->sessionToday($otherDoctor);

        $this->actingAs($doctor)->get(route('appointments.welcome', ['doctor_id' => $otherDoctor->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointment/Welcome')
                ->where('stats.today', 1)
                ->where('stats.pending', 1)
                ->where('stats.confirmed', 0)
                ->where('stats.availableDoctors', 1)
                ->has('upcomingAppointments', 1)
                ->where('upcomingAppointments.0.patient.id', $patient->id)
                ->where('upcomingAppointments.0.doctor.id', $doctor->id)
                ->has('doctors', 0)
                ->where('filters.doctor_id', $doctor->id)
                ->where('isAdmin', false)
            );
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function doctor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('doctor');
        Doctor::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function appointment(
        User $doctor,
        Patient $patient,
        string $date,
        AppointmentStatus $status,
        string $starttime
    ): Appointment {
        return Appointment::create([
            'title' => 'Cita de prueba',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => $status,
            'date' => $date,
            'starttime' => $starttime,
            'endtime' => '09:30',
            'attended' => false,
            'active' => ! $status->isTerminal(),
            'cancelled_at' => $status === AppointmentStatus::Cancelled ? now() : null,
        ]);
    }

    private function sessionToday(User $doctor): MedicalSession
    {
        return MedicalSession::factory()->create([
            'doctor_id' => $doctor->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'consultation_minutes' => 30,
            'capacity' => 2,
        ]);
    }
}

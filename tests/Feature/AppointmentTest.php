<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_appointment_is_created_as_pending_with_nullable_drug(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)->post(route('appointments.store'), $this->payload($doctor, $patient))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::Pending->value,
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'drug_id' => null,
        ]);
    }

    public function test_overlapping_appointment_is_rejected(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $date = $this->weekdayDate();

        Appointment::create([
            'title' => 'Cita existente',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::Confirmed,
            'date' => $date,
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'active' => true,
        ]);

        $this->actingAs($doctor)->post(route('appointments.store'), $this->payload($doctor, $patient, $date, '09:30', '10:30'))
            ->assertSessionHasErrors('starttime');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_cancelled_appointment_does_not_block_the_slot(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $date = $this->weekdayDate();

        Appointment::create([
            'title' => 'Cita cancelada',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::Cancelled,
            'date' => $date,
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'active' => false,
        ]);

        $this->actingAs($doctor)->post(route('appointments.store'), $this->payload($doctor, $patient, $date))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_outside_business_hours_and_weekends_are_rejected(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)
            ->post(route('appointments.store'), $this->payload($doctor, $patient, $this->weekdayDate(), '07:00', '08:00'))
            ->assertSessionHasErrors('starttime');

        $this->actingAs($doctor)
            ->post(route('appointments.store'), $this->payload($doctor, $patient, $this->saturdayDate()))
            ->assertSessionHasErrors('starttime');
    }

    public function test_terminal_initial_status_is_rejected_and_completion_marks_attendance(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)->post(route('appointments.store'), $this->payload(
            $doctor,
            $patient,
            $this->weekdayDate(),
            '09:00',
            '10:00',
            ['status' => AppointmentStatus::Completed->value]
        ))->assertSessionHasErrors('status');

        $appointment = Appointment::create([
            'title' => 'Cita confirmada',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::Confirmed,
            'date' => $this->weekdayDate(),
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'active' => true,
        ]);

        $this->actingAs($doctor)->post(route('appointments.status', $appointment), [
            'status' => AppointmentStatus::Completed->value,
            'reason' => 'Consulta finalizada.',
        ])->assertStatus(302);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Completed->value,
            'attended' => true,
            'active' => false,
        ]);
    }

    public function test_drug_must_exist_in_drugs_table(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)->post(route('appointments.store'), $this->payload(
            $doctor,
            $patient,
            $this->weekdayDate(),
            '09:00',
            '10:00',
            ['drug_id' => 999999]
        ))->assertSessionHasErrors('drug_id');
    }

    public function test_doctor_can_view_edit_and_update_an_appointment(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $date = $this->weekdayDate();

        $appointment = Appointment::create([
            'title' => 'Cita original',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::Confirmed,
            'date' => $date,
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'active' => true,
        ]);

        $this->actingAs($doctor)->get(route('appointments.show', $appointment))
            ->assertInertia(fn ($page) => $page
                ->component('Appointment/show')
                ->where('appointment.id', $appointment->id)
                ->where('statuses', AppointmentStatus::options())
            );

        $this->actingAs($doctor)->get(route('appointments.edit', $appointment))
            ->assertInertia(fn ($page) => $page
                ->component('Appointment/edit')
                ->where('appointments.id', $appointment->id)
                ->where('statuses', AppointmentStatus::options())
                ->where('availability.start_time', config('appointments.start_time'))
            );

        $this->actingAs($doctor)->put(
            route('appointments.update', $appointment),
            $this->payload($doctor, $patient, $date, '11:00', '12:00')
        )->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'starttime' => '11:00:00',
            'endtime' => '12:00:00',
        ]);
    }

    public function test_appointment_pages_share_statuses_drugs_and_availability(): void
    {
        $doctor = $this->doctor();
        $drug = Drug::factory()->create();

        $this->actingAs($doctor)->get(route('appointments.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Appointment/index')
                ->where('statuses', AppointmentStatus::options())
            );

        $this->actingAs($doctor)->get(route('appointments.create'))
            ->assertInertia(fn ($page) => $page
                ->component('Appointment/create')
                ->where('statuses', AppointmentStatus::options())
                ->where('drugs.0.id', $drug->id)
                ->where('availability.end_time', config('appointments.end_time'))
            );
    }

    public function test_dashboard_exposes_status_based_kpis(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();

        Appointment::create([
            'title' => 'Cita de hoy',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => AppointmentStatus::Pending,
            'date' => now()->toDateString(),
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'active' => true,
        ]);

        $this->actingAs($doctor)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('Kpis.AppointmentsToday', 1)
                ->where('Kpis.PendingAppointments', 1)
                ->where('Kpis.CompletedAppointments', 0)
                ->where('Kpis.ActiveDoctors', 1)
                ->where('Kpis.TotalBilling', 0)
            );
    }

    public function test_destroy_cancels_the_appointment_instead_of_deleting_it(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $drug = Drug::factory()->create();

        $appointment = Appointment::create([
            'title' => 'Cita a cancelar',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'drug_id' => $drug->id,
            'status' => AppointmentStatus::Confirmed,
            'date' => $this->weekdayDate(),
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'active' => true,
        ]);

        $this->actingAs($doctor)->delete(route('appointments.destroy', $appointment->id))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Cancelled->value,
            'active' => false,
        ]);
    }

    private function doctor(): User
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        return $doctor;
    }

    private function weekdayDate(): string
    {
        return now()->startOfWeek()->addWeek()->toDateString();
    }

    private function saturdayDate(): string
    {
        return now()->startOfWeek()->addDays(5)->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        User $doctor,
        Patient $patient,
        ?string $date = null,
        string $start = '09:00',
        string $end = '10:00',
        array $extra = []
    ): array {
        return array_merge([
            'title' => 'Consulta de prueba',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date' => $date ?? $this->weekdayDate(),
            'starttime' => $start,
            'endtime' => $end,
            'active' => true,
        ], $extra);
    }
}

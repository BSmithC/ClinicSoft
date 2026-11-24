<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentBookingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_doctor_cannot_book_into_another_doctors_session(): void
    {
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $patient = Patient::factory()->create();
        $session = $this->clinicalSession($otherDoctor);

        $this->actingAs($doctor)->post(route('appointments.store'), $this->payload(
            $doctor,
            $patient,
            $session,
            '09:00',
            '09:30'
        ))->assertForbidden();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_doctor_cannot_query_availability_by_foreign_session_id(): void
    {
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $session = $this->clinicalSession($otherDoctor);

        $this->actingAs($doctor)->get(route('availability.index', [
            'session_id' => $session->id,
        ]))->assertForbidden();
    }

    public function test_doctor_cannot_create_or_move_sessions_to_another_doctor(): void
    {
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $date = $this->weekdayDate();

        $this->actingAs($doctor)->post(route('sessions.store'), [
            'doctor_id' => $otherDoctor->id,
            'title' => 'Sesión ajena',
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'consultation_minutes' => 30,
            'capacity' => 1,
        ])->assertRedirect(route('sessions.index'));

        $session = MedicalSession::firstOrFail();
        $this->assertSame((int) $doctor->id, (int) $session->doctor_id);

        $this->actingAs($doctor)->put(route('sessions.update', $session), [
            'doctor_id' => $otherDoctor->id,
        ])->assertRedirect(route('sessions.index'));

        $this->assertSame((int) $doctor->id, (int) $session->fresh()->doctor_id);
    }

    public function test_update_with_terminal_status_does_not_partially_write(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $appointment = $this->appointment($doctor, $patient, AppointmentStatus::Confirmed);

        $this->actingAs($doctor)->put(route('appointments.update', $appointment), [
            'title' => 'Cita original',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date' => $appointment->date->toDateString(),
            'starttime' => '11:00',
            'endtime' => '12:00',
            'status' => AppointmentStatus::Cancelled->value,
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Confirmed->value,
            'starttime' => '09:00:00',
            'active' => true,
        ]);
    }

    public function test_client_cannot_create_inactive_appointments(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $session = $this->clinicalSession($doctor);

        $this->actingAs($doctor)->post(
            route('appointments.store'),
            $this->payload($doctor, $patient, $session, '09:00', '09:30', ['active' => false])
        )->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'active' => true,
        ]);
    }

    public function test_blocked_date_rejects_legacy_appointments(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $date = $this->weekdayDate();

        BlockedDate::create(['doctor_id' => $doctor->id, 'date' => $date]);

        $this->actingAs($doctor)->post(route('appointments.store'), [
            'title' => 'Cita en fecha bloqueada',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date' => $date,
            'starttime' => '09:00',
            'endtime' => '10:00',
        ])->assertSessionHasErrors('starttime');
    }

    public function test_reschedule_keeps_session_validation_when_session_id_is_omitted(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $session = $this->clinicalSession($doctor);
        $appointment = $this->appointment($doctor, $patient, AppointmentStatus::Confirmed, $session);

        $this->actingAs($doctor)->put(route('appointments.update', $appointment), [
            'title' => 'Cita original',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'date' => $session->date->toDateString(),
            'starttime' => '18:30',
            'endtime' => '19:00',
        ])->assertSessionHasErrors('starttime');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'starttime' => '09:00:00',
        ]);
    }

    public function test_calendar_create_returns_to_the_current_agenda_view(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $session = $this->clinicalSession($doctor);
        $agendaUrl = route('agenda.index', [
            'view' => 'week',
            'date' => $session->date->toDateString(),
            'status' => 'pending',
        ]);

        $this->actingAs($doctor)->from($agendaUrl)->post(
            route('appointments.store'),
            $this->payload($doctor, $patient, $session, '09:00', '09:30', ['from_calendar' => true])
        )->assertRedirect($agendaUrl);

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_session_capacity_allows_multiple_patients_in_different_slots(): void
    {
        $doctor = $this->doctor();
        $session = $this->clinicalSession($doctor, 2);
        $firstPatient = Patient::factory()->create();
        $secondPatient = Patient::factory()->create();
        $booking = app(AppointmentBookingService::class);

        $booking->book($this->payload($doctor, $firstPatient, $session, '09:00', '09:30'));
        $booking->book($this->payload($doctor, $secondPatient, $session, '09:30', '10:00'));

        $this->assertDatabaseCount('appointments', 2);
        $this->assertSame(2, Appointment::query()->where('session_id', $session->id)->where('active', true)->count());
    }

    public function test_session_rejects_overlapping_slots_even_when_capacity_is_available(): void
    {
        $doctor = $this->doctor();
        $session = $this->clinicalSession($doctor, 5);
        $firstPatient = Patient::factory()->create();
        $secondPatient = Patient::factory()->create();
        $booking = app(AppointmentBookingService::class);

        $booking->book($this->payload($doctor, $firstPatient, $session, '09:00', '09:30'));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $booking->book($this->payload($doctor, $secondPatient, $session, '09:15', '09:45'));
    }

    public function test_calendar_contract_exposes_events_and_server_side_capabilities(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $appointment = $this->appointment($doctor, $patient, AppointmentStatus::Confirmed);

        $this->actingAs($doctor)->get(route('agenda.index', [
            'view' => 'day',
            'date' => $appointment->date->toDateString(),
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('Agenda/index')
            ->where('filters.view', 'day')
            ->where('filters.date', $appointment->date->toDateString())
            ->has('events', 1)
            ->where('events.0.type', 'appointment')
            ->where('events.0.can.edit', true)
            ->where('events.0.can.viewHistory', true)
            ->where('events.0.status', AppointmentStatus::Confirmed->value)
            ->has('daySummary.' . $appointment->date->toDateString())
            ->where('timezone', config('app.timezone'))
        );
    }

    private function doctor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('doctor');
        Doctor::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function weekdayDate(): string
    {
        return now()->startOfWeek()->addWeek()->toDateString();
    }

    private function clinicalSession(User $doctor, int $capacity = 1): MedicalSession
    {
        return MedicalSession::factory()->create([
            'doctor_id' => $doctor->id,
            'date' => $this->weekdayDate(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'consultation_minutes' => 30,
            'capacity' => $capacity,
        ]);
    }

    private function appointment(
        User $doctor,
        Patient $patient,
        AppointmentStatus $status,
        ?MedicalSession $session = null
    ): Appointment {
        return Appointment::create([
            'title' => 'Cita de prueba',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'session_id' => $session?->id,
            'status' => $status,
            'date' => $session?->date?->toDateString() ?? $this->weekdayDate(),
            'starttime' => '09:00:00',
            'endtime' => '09:30:00',
            'attended' => false,
            'active' => ! $status->isTerminal(),
        ]);
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function payload(
        User $doctor,
        Patient $patient,
        MedicalSession $session,
        string $start,
        string $end,
        array $extra = []
    ): array {
        return array_merge([
            'title' => 'Cita de prueba',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'session_id' => $session->id,
            'date' => $session->date->toDateString(),
            'starttime' => $start,
            'endtime' => $end,
        ], $extra);
    }
}

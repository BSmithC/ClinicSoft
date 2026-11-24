<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\MedicalSessionStatus;
use App\Models\Appointment;
use App\Models\BlockedDate;
use App\Models\Doctor;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AppointmentBookingService;
use App\Services\AvailabilityService;
use App\Services\MedicalSessionService;
use Database\Seeders\ClinicalSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClinicalDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_session_is_created_with_slots_and_rejects_overlaps_and_blocked_dates(): void
    {
        $doctor = $this->doctor();
        $date = $this->weekdayDate();

        $this->actingAs($doctor)->post(route('sessions.store'), $this->sessionPayload($doctor, $date))
            ->assertRedirect(route('sessions.index'));

        $session = MedicalSession::firstOrFail();
        $this->assertCount(6, app(AvailabilityService::class)->slotsForSession($session));
        $this->assertDatabaseHas('medical_sessions', [
            'doctor_id' => $doctor->id,
            'status' => MedicalSessionStatus::Scheduled->value,
            'capacity' => 3,
        ]);

        $this->actingAs($doctor)->post(route('sessions.store'), $this->sessionPayload($doctor, $date, [
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]))->assertSessionHasErrors('start_time');

        BlockedDate::create(['doctor_id' => $doctor->id, 'date' => now()->addWeeks(2)->startOfWeek()->toDateString()]);
        $this->actingAs($doctor)->post(route('sessions.store'), $this->sessionPayload(
            $doctor,
            now()->addWeeks(2)->startOfWeek()->toDateString()
        ))->assertSessionHasErrors('date');
    }

    public function test_booking_uses_real_session_slots_and_enforces_capacity(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $session = MedicalSession::factory()->create([
            'doctor_id' => $doctor->id,
            'date' => $this->weekdayDate(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'consultation_minutes' => 30,
            'capacity' => 1,
        ]);

        $this->actingAs($doctor)->get(route('availability.index', [
            'doctor_id' => $doctor->id,
            'date' => $session->date->toDateString(),
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('Appointment/create')
            ->where('availabilitySlots.' . $session->id . '.0.start', '09:00')
            ->where('availabilitySlots.' . $session->id . '.0.available', true)
        );

        $this->actingAs($doctor)->post(route('appointments.store'), $this->bookingPayload($doctor, $patient, $session, '09:00', '09:30'))
            ->assertRedirect(route('appointments.index'));

        $this->assertDatabaseHas('appointments', [
            'session_id' => $session->id,
            'patient_id' => $patient->id,
            'starttime' => '09:00:00',
            'endtime' => '09:30:00',
            'status' => AppointmentStatus::Pending->value,
        ]);

        $this->actingAs($doctor)->post(route('appointments.store'), $this->bookingPayload($doctor, $patient, $session, '09:30', '10:00'))
            ->assertSessionHasErrors('starttime');
    }

    public function test_same_patient_cannot_be_booked_twice_at_the_same_time(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $patient = Patient::factory()->create();
        $date = $this->weekdayDate();
        $sessionA = MedicalSession::factory()->create(['doctor_id' => $doctorA->id, 'date' => $date, 'capacity' => 5]);
        $sessionB = MedicalSession::factory()->create(['doctor_id' => $doctorB->id, 'date' => $date, 'capacity' => 5]);

        $booking = app(AppointmentBookingService::class);
        $booking->book($this->bookingPayload($doctorA, $patient, $sessionA, '09:00', '09:30'));

        $this->expectException(ValidationException::class);
        $booking->book($this->bookingPayload($doctorB, $patient, $sessionB, '09:00', '09:30'));
    }

    public function test_completed_appointment_frees_its_slot_for_a_new_booking(): void
    {
        $doctor = $this->doctor();
        $firstPatient = Patient::factory()->create();
        $secondPatient = Patient::factory()->create();
        $session = MedicalSession::factory()->create([
            'doctor_id' => $doctor->id,
            'date' => $this->weekdayDate(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'consultation_minutes' => 30,
            'capacity' => 2,
        ]);

        $this->appointment($doctor, $firstPatient, AppointmentStatus::Completed, $session);

        $booking = app(AppointmentBookingService::class);
        $newAppointment = $booking->book($this->bookingPayload($doctor, $secondPatient, $session, '09:00', '09:30'));

        $this->assertTrue($newAppointment->exists);
        $this->assertDatabaseHas('appointments', [
            'id' => $newAppointment->id,
            'patient_id' => $secondPatient->id,
            'starttime' => '09:00:00',
        ]);
    }

    public function test_cancel_rechecks_the_persisted_status_inside_the_transaction(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $appointment = $this->appointment($doctor, $patient);
        $booking = app(AppointmentBookingService::class);

        // The caller keeps a stale "pending" instance after the appointment was completed.
        $stale = $appointment->fresh();
        $booking->changeStatus($appointment, AppointmentStatus::Confirmed);
        $booking->changeStatus($appointment->fresh(), AppointmentStatus::Completed);

        $this->expectException(ValidationException::class);
        $booking->cancel($stale);
    }

    public function test_status_machine_rejects_arbitrary_transitions_and_keeps_history(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $appointment = $this->appointment($doctor, $patient);

        $booking = app(AppointmentBookingService::class);
        $this->assertSame(
            AppointmentStatus::Confirmed,
            $booking->changeStatus($appointment, AppointmentStatus::Confirmed)->status
        );

        try {
            $booking->changeStatus($appointment->fresh(), AppointmentStatus::Pending);
            $this->fail('Se permitió una transición inválida.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $cancelled = $booking->cancel($appointment->fresh(), 'Paciente no asistirá.');
        $this->assertFalse($cancelled->active);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertDatabaseHas('appointment_histories', [
            'appointment_id' => $appointment->id,
            'to_status' => AppointmentStatus::Cancelled->value,
            'reason' => 'Paciente no asistirá.',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => 'appointment',
            'subject_id' => $appointment->id,
            'action' => 'appointment.cancelled',
        ]);
    }

    public function test_cancelling_a_session_cancels_its_appointments_with_history(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $session = MedicalSession::factory()->create([
            'doctor_id' => $doctor->id,
            'date' => $this->weekdayDate(),
        ]);
        $appointment = $this->appointment($doctor, $patient, AppointmentStatus::Confirmed, $session);

        $this->actingAs($doctor)->post(route('sessions.cancel', $session), ['reason' => 'Ausencia del médico'])
            ->assertRedirect(route('sessions.index'));

        $this->assertDatabaseHas('medical_sessions', [
            'id' => $session->id,
            'status' => MedicalSessionStatus::Cancelled->value,
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Cancelled->value,
            'active' => false,
        ]);
        $this->assertDatabaseHas('appointment_histories', [
            'appointment_id' => $appointment->id,
            'reason' => 'Ausencia del médico',
        ]);
    }

    public function test_doctor_cannot_access_another_doctors_clinical_data(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $patient = Patient::factory()->create();
        $session = MedicalSession::factory()->create(['doctor_id' => $doctorB->id, 'date' => $this->weekdayDate()]);
        $appointment = $this->appointment($doctorB, $patient, AppointmentStatus::Confirmed, $session);

        $this->actingAs($doctorA)->get(route('appointments.show', $appointment))->assertForbidden();
        $this->actingAs($doctorA)->get(route('sessions.show', $session))->assertForbidden();
        $this->actingAs($doctorA)->get(route('patients.show', $patient))->assertForbidden();
        $this->actingAs($doctorA)->get(route('doctors.show', $doctorB->doctorProfile))->assertForbidden();
    }

    public function test_dashboard_exposes_full_real_kpis(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $specialty = Specialty::factory()->create();
        $session = MedicalSession::factory()->create([
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'date' => now()->toDateString(),
        ]);
        $this->appointment($doctor, $patient, AppointmentStatus::Confirmed, $session);

        $this->actingAs($doctor)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('Kpis.SpecialtiesAll', 1)
                ->where('Kpis.ConfirmedAppointments', 1)
                ->where('Kpis.CancelledAppointments', 0)
                ->where('Kpis.ActiveSessions', 1)
                ->where('Kpis.AppointmentsToday', 1)
                ->has('upcomingAppointments', 1)
                ->has('recentActivity')
            );
    }

    public function test_clinical_seeder_backfills_doctor_profiles_without_rewriting_appointments(): void
    {
        $doctor = $this->doctor(false);
        $patient = Patient::factory()->create();
        $appointment = $this->appointment($doctor, $patient);
        $this->assertDatabaseMissing('doctors', ['user_id' => $doctor->id]);

        $this->seed(ClinicalSeeder::class);

        $this->assertDatabaseHas('doctors', ['user_id' => $doctor->id, 'active' => true]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'doctor_id' => $doctor->id]);
        $profile = Doctor::where('user_id', $doctor->id)->firstOrFail();
        $this->assertSame(1, $profile->specialties()->count());
    }

    public function test_concurrent_capacity_is_checked_inside_the_transaction(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $session = MedicalSession::factory()->create([
            'doctor_id' => $doctor->id,
            'date' => $this->weekdayDate(),
            'capacity' => 1,
        ]);
        $booking = app(AppointmentBookingService::class);
        $booking->book($this->bookingPayload($doctor, $patient, $session, '09:00', '09:30'));

        $otherPatient = Patient::factory()->create();
        $this->expectException(ValidationException::class);
        DB::transaction(fn () => $booking->book($this->bookingPayload($doctor, $otherPatient, $session, '09:30', '10:00')));
    }

    private function doctor(bool $withProfile = true): User
    {
        $user = User::factory()->create();
        $user->assignRole('doctor');

        if ($withProfile) {
            Doctor::factory()->create(['user_id' => $user->id]);
        }

        return $user;
    }

    private function weekdayDate(): string
    {
        return now()->startOfWeek()->addWeek()->toDateString();
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function sessionPayload(User $doctor, string $date, array $extra = []): array
    {
        return array_merge([
            'doctor_id' => $doctor->id,
            'title' => 'Consulta de prueba',
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'consultation_minutes' => 30,
            'capacity' => 3,
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(
        User $doctor,
        Patient $patient,
        MedicalSession $session,
        string $start,
        string $end
    ): array {
        return [
            'title' => 'Consulta de prueba',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'session_id' => $session->id,
            'date' => $session->date->toDateString(),
            'starttime' => $start,
            'endtime' => $end,
            'active' => true,
        ];
    }

    private function appointment(
        User $doctor,
        Patient $patient,
        AppointmentStatus $status = AppointmentStatus::Pending,
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
            'attended' => $status === AppointmentStatus::Completed,
            'active' => ! $status->isTerminal(),
        ]);
    }
}

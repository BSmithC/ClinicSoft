<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Drug;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_are_seeded_and_existing_users_become_doctors(): void
    {
        $legacyUser = User::factory()->create();

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'doctor']);
        $this->assertDatabaseHas('roles', ['name' => 'patient']);
        $this->assertTrue($legacyUser->fresh()->hasRole('doctor'));
    }

    public function test_doctor_can_access_clinical_resources(): void
    {
        $this->seed(RoleSeeder::class);

        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $this->actingAs($doctor)->get(route('dashboard'))->assertOk();
        $this->actingAs($doctor)->get(route('appointments.index'))->assertOk();
        $this->actingAs($doctor)->get(route('patients.index'))->assertOk();
    }

    public function test_user_without_role_is_forbidden_but_can_edit_profile(): void
    {
        $this->seed(RoleSeeder::class);

        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('appointments.index'))->assertForbidden();
        $this->actingAs($outsider)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($outsider)->get(route('profile.edit'))->assertOk();
    }

    public function test_security_headers_are_added_to_responses(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('password.email'), ['email' => 'desconocido@example.com'])
                ->assertSessionHasErrors('email');
        }

        $this->post(route('password.email'), ['email' => 'desconocido@example.com'])
            ->assertStatus(429);
    }

    public function test_create_forms_only_expose_fields_needed_by_selects(): void
    {
        $this->seed(RoleSeeder::class);

        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');
        $patient = Patient::factory()->create();
        $drug = Drug::factory()->create();

        $this->actingAs($doctor)->get(route('appointments.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('doctors', 1)
                ->has('patients', 1)
                ->has('drugs', 1)
                ->missing('doctors.0.email')
                ->missing('doctors.0.address')
                ->missing('patients.0.insurance')
                ->missing('patients.0.phone_number')
                ->missing('drugs.0.description')
            );

        $this->actingAs($doctor)->get(route('bills.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users', 1)
                ->has('patients', 1)
                ->has('drugs', 1)
                ->missing('users.0.email')
                ->missing('users.0.insurance')
                ->missing('patients.0.address')
                ->missing('drugs.0.description')
            );
    }

    public function test_doctor_can_open_detail_and_edit_pages_of_all_modules(): void
    {
        $this->seed(RoleSeeder::class);

        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');
        $patient = Patient::factory()->create();
        $drug = Drug::factory()->create();

        $appointment = Appointment::create([
            'title' => 'Cita de prueba',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'drug_id' => $drug->id,
            'status' => AppointmentStatus::Pending,
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'starttime' => '09:00:00',
            'endtime' => '10:00:00',
            'attended' => false,
            'active' => true,
        ]);

        $bill = Bill::create([
            'drug_id' => $drug->id,
            'patient_id' => $patient->id,
            'users_id' => $doctor->id,
            'itbis' => 0,
            'sub_total' => 100,
            'total' => 100,
            'payment_type' => 'cash',
            'status' => true,
        ]);

        $history = MedicalHistory::create([
            'diagnosis' => 'Diagnóstico',
            'reason' => 'Motivo',
            'symptoms' => 'Síntomas',
            'diagnosis_description' => 'Descripción',
            'description' => 'Observación',
            'blood_pressure' => '120/80',
            'temperature' => '36.5',
            'weight' => '70',
            'heart_rate' => '70',
            'observation' => 'Sin novedades',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'drug_id' => $drug->id,
        ]);

        $urls = [
            route('appointments.show', $appointment),
            route('appointments.edit', $appointment),
            route('patients.show', $patient),
            route('patients.edit', $patient),
            route('drugs.show', $drug),
            route('drugs.edit', $drug),
            route('bills.show', $bill),
            route('bills.edit', $bill),
            route('medicalhistories.show', $history),
            route('medicalhistories.edit', $history),
        ];

        foreach ($urls as $url) {
            $this->actingAs($doctor)->get($url)->assertStatus(200, $url);
        }
    }

    public function test_inertia_shares_only_safe_user_fields(): void
    {
        $this->seed(RoleSeeder::class);

        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $this->actingAs($doctor)->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.id', $doctor->id)
                ->where('auth.user.name', $doctor->name)
                ->where('auth.user.email', $doctor->email)
                ->where('auth.user.roles', ['doctor'])
                ->missing('auth.user.address')
                ->missing('auth.user.phone_number')
                ->missing('auth.user.insurance')
            );
    }
}

<?php

/**
 * Verificacion de integridad de la BD MySQL sin dejar datos:
 * FK, indices, roles, reglas de disponibilidad y cancelacion logica.
 * Uso: php scripts/verify_mysql.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Drug;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentAvailabilityService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('FALLO: '.$message);
    }

    echo "OK: $message\n";
}

$db = config('database.connections.mysql.database');
$fks = DB::select("
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'appointments' AND REFERENCED_TABLE_NAME IS NOT NULL
", [$db]);
$names = array_map(fn ($fk) => $fk->COLUMN_NAME.'->'.$fk->REFERENCED_TABLE_NAME, $fks);
sort($names);
$expectedFks = [
    'doctor_id->users',
    'drug_id->drugs',
    'patient_id->patients',
    'rescheduled_from_id->appointments',
    'session_id->medical_sessions',
    'specialty_id->specialties',
];
sort($expectedFks);
check($names === $expectedFks, 'FK de appointments: '.implode(', ', $names));

$indexes = array_map(fn ($row) => $row->Key_name, DB::select('SHOW INDEX FROM appointments'));
foreach ([
    'appointments_doctor_date_index',
    'appointments_patient_id_index',
    'appointments_status_index',
    'appointments_date_index',
    'appointments_doctor_date_start_active_unique',
    'appointments_session_start_index',
    'appointments_patient_date_status_index',
    'appointments_session_slot_active_unique',
] as $index) {
    check(in_array($index, $indexes, true), "indice $index presente");
}

$roles = DB::table('roles')->pluck('name')->all();
sort($roles);
$expectedRoles = ['admin', 'doctor', 'patient'];
check($roles === $expectedRoles, 'roles sembrados: '.implode(', ', $roles));

$appointmentsBefore = Appointment::count();

DB::beginTransaction();

try {
    $doctor = User::factory()->create();
    $doctor->assignRole('doctor');
    $patient = Patient::factory()->create();
    $drug = Drug::factory()->create();
    $date = now()->startOfWeek()->addWeek()->toDateString();
    $service = new AppointmentAvailabilityService();

    check($service->validate($doctor->id, $date, '09:00:00', '10:00:00') === [], 'hueco libre aceptado en MySQL');

    $appointment = Appointment::create([
        'title' => 'Prueba de verificacion',
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'drug_id' => $drug->id,
        'status' => AppointmentStatus::Pending,
        'date' => $date,
        'starttime' => '09:00:00',
        'endtime' => '10:00:00',
        'attended' => false,
        'active' => true,
    ]);
    check($appointment->exists, 'cita creada en MySQL con FK reales');

    $overlap = $service->validate($doctor->id, $date, '09:30:00', '10:30:00');
    check($overlap !== [] && str_contains($overlap[0], 'solapa'), 'solape rechazado en MySQL');

    check($service->validate($doctor->id, $date, '10:00:00', '11:00:00') === [], 'hueco contiguo aceptado');

    $appointment->update(['status' => AppointmentStatus::Cancelled, 'active' => false]);
    check($service->validate($doctor->id, $date, '09:00:00', '10:00:00') === [], 'cancelacion logica libera el hueco');

    try {
        Appointment::create([
            'title' => 'FK invalida',
            'doctor_id' => $doctor->id,
            'patient_id' => 999999,
            'date' => $date,
            'starttime' => '12:00:00',
            'endtime' => '13:00:00',
            'attended' => false,
            'active' => true,
        ]);
        check(false, 'FK de patient_id rechaza id inexistente');
    } catch (QueryException $e) {
        check(str_contains($e->getMessage(), 'FOREIGN KEY'), 'FK de patient_id rechaza id inexistente');
    }
} finally {
    DB::rollBack();
}

check(Appointment::count() === $appointmentsBefore, 'transaccion revertida: no quedan citas de prueba');
echo "VERIFICACION MySQL COMPLETA\n";

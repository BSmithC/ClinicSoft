<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backfills the clinical domain for installations that already have users and
 * appointments. doctor_id keeps being users.id, so no existing row is rewritten.
 */
class ClinicalSeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            'Medicina general' => 'General practice and primary care.',
            'Cardiología' => 'Cardiovascular care.',
            'Dermatología' => 'Skin, hair and nail care.',
            'Pediatría' => 'Child health care.',
            'Ginecología' => 'Women’s health care.',
            'Ortopedia' => 'Musculoskeletal care.',
        ];

        $ids = [];

        foreach ($specialties as $name => $description) {
            $specialty = Specialty::withTrashed()->firstOrCreate(
                ['slug' => Str::slug($name, '-')],
                ['name' => $name, 'description' => $description, 'active' => true]
            );

            if ($specialty->trashed()) {
                $specialty->restore();
            }

            $specialty->update(['active' => true]);
            $ids[$name] = $specialty->id;
        }

        $general = $ids['Medicina general'];

        User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'doctor'))
            ->orderBy('id')
            ->each(function (User $user) use ($general) {
                $doctor = Doctor::withTrashed()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['active' => true, 'license_number' => null]
                );

                if ($doctor->trashed()) {
                    $doctor->restore();
                }

                $doctor->update(['active' => true]);
                $doctor->specialties()->syncWithoutDetaching([$general]);
            });
    }
}

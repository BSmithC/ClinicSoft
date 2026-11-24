<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+1 week');
        $end = (clone $start)->modify('+1 hour');

        return [
            'title' => $this->faker->sentence(3),
            'doctor_id' => User::factory(),
            'patient_id' => Patient::factory(),
            'drug_id' => null,
            'status' => AppointmentStatus::Pending,
            'attended' => false,
            'date' => $start->format('Y-m-d'),
            'starttime' => $start->format('H:i:s'),
            'endtime' => $end->format('H:i:s'),
            'active' => true,
        ];
    }
}

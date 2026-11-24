<?php

namespace Database\Factories;

use App\Enums\MedicalSessionStatus;
use App\Models\MedicalSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalSession>
 */
class MedicalSessionFactory extends Factory
{
    protected $model = MedicalSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => User::factory(),
            'specialty_id' => null,
            'title' => 'Consulta ' . $this->faker->word(),
            'date' => now()->addWeek()->startOfWeek()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'consultation_minutes' => 30,
            'capacity' => 6,
            'status' => MedicalSessionStatus::Scheduled,
            'notes' => null,
        ];
    }
}

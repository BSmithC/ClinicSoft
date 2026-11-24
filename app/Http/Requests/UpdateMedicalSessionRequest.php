<?php

namespace App\Http\Requests;

use App\Enums\MedicalSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['sometimes', 'exists:users,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'consultation_minutes' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(MedicalSessionStatus::class)],
            'notes' => ['nullable', 'string'],
            'breaks' => ['sometimes', 'array'],
            'breaks.*.start_time' => ['required_with:breaks', 'date_format:H:i'],
            'breaks.*.end_time' => ['required_with:breaks', 'date_format:H:i'],
            'breaks.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

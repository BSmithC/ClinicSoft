<?php

namespace App\Http\Requests;

use App\Enums\MedicalSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicalSessionRequest extends FormRequest
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
            'doctor_id' => ['required', 'exists:users,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'consultation_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(MedicalSessionStatus::class)],
            'notes' => ['nullable', 'string'],
            'breaks' => ['sometimes', 'array'],
            'breaks.*.start_time' => ['required_with:breaks', 'date_format:H:i'],
            'breaks.*.end_time' => ['required_with:breaks', 'date_format:H:i', 'after:breaks.*.start_time'],
            'breaks.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

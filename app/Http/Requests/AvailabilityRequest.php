<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
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
            'doctor_id' => ['required_without:session_id', 'exists:users,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'session_id' => ['nullable', 'exists:medical_sessions,id'],
            'date' => ['required_without:session_id', 'date_format:Y-m-d'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'ignore_id' => ['nullable', 'exists:appointments,id'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'doctor_id' => ['required', 'exists:users,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'drug_id' => ['nullable', 'exists:drugs,id'],
            'session_id' => ['nullable', 'exists:medical_sessions,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'status' => ['sometimes', Rule::enum(AppointmentStatus::class)],
            'attended' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'date' => ['required', 'date_format:Y-m-d'],
            'starttime' => ['required', 'date_format:H:i,H:i:s'],
            'endtime' => ['required', 'date_format:H:i,H:i:s', 'after:starttime'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

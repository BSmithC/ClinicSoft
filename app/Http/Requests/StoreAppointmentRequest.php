<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
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
            'status' => [
                'sometimes',
                Rule::enum(AppointmentStatus::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! in_array($value, [
                        AppointmentStatus::Pending->value,
                        AppointmentStatus::Confirmed->value,
                    ], true)) {
                        $fail('El estado inicial permitido es pendiente o confirmada.');
                    }
                },
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'starttime' => ['required', 'date_format:H:i,H:i:s'],
            'endtime' => ['required', 'date_format:H:i,H:i:s', 'after:starttime'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}

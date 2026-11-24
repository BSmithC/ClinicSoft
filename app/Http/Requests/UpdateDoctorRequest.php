<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
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
        $doctorId = $this->route('doctor')?->id;

        return [
            'license_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('doctors', 'license_number')->ignore($doctorId),
            ],
            'bio' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'specialties' => ['sometimes', 'array'],
            'specialties.*' => ['exists:specialties,id'],
        ];
    }
}

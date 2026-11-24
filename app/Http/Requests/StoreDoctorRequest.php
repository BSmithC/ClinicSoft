<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id', Rule::unique('doctors', 'user_id')],
            'license_number' => ['nullable', 'string', 'max:100', Rule::unique('doctors', 'license_number')],
            'bio' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'specialties' => ['sometimes', 'array'],
            'specialties.*' => ['exists:specialties,id'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
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
        $id = $this->route('patient')?->id;

        return [
            'DNI' => ['required', 'string', 'max:255', Rule::unique('patients', 'DNI')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'sur_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'second_sur_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'nacionality' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:50'],
            'sex' => ['required', 'string', 'max:50'],
            'marital_status' => ['required', 'string', 'max:50'],
            'ocupation' => ['nullable', 'string', 'max:255'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'blood_type' => ['nullable', 'string', 'max:3'],
            'insurance' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'boolean'],
            'email' => ['required', 'email', 'max:255', Rule::unique('patients', 'email')->ignore($id)],
        ];
    }
}

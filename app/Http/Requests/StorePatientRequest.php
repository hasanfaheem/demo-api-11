<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'mrn'           => ['required', 'string', 'max:255', 'unique:patients,mrn'],
        ];
    }

    public function messages(): array
    {
        return [
            'mrn.unique'           => 'A patient with this MRN already exists.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
        ];
    }
}
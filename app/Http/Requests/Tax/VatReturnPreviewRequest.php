<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class VatReturnPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper((string) $this->input('country_code')),
            'state_code'   => $this->filled('state_code') ? strtoupper((string)$this->input('state_code')) : null,
            'local_code'   => $this->filled('local_code') ? strtoupper((string)$this->input('local_code')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'period'       => ['required', 'date_format:Y-m'],
            'tax_year'     => ['required', 'integer', 'min:2000', 'max:2100'],
            'country_code' => ['required', 'string', 'size:2'],
            'state_code'   => ['nullable', 'string', 'max:10'],
            'local_code'   => ['nullable', 'string', 'max:50'],
        ];
    }
}

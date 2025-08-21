<?php

namespace App\Http\Requests\Tax;

use App\Models\TaxJurisdiction;
use Illuminate\Foundation\Http\FormRequest;

class TaxVersionIndexFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper((string) $this->input('country_code')),
            'state_code'   => $this->filled('state_code') ? strtoupper((string) $this->input('state_code')) : null,
            'local_code'   => $this->filled('local_code') ? strtoupper((string) $this->input('local_code')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', 'size:2'],
            'state_code'   => ['nullable', 'string', 'max:10'],
            'local_code'   => ['nullable', 'string', 'max:50'],
            'tax_year'     => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (!TaxJurisdiction::country($this->country_code)->exists()) {
                $v->errors()->add('country_code', 'Unknown or unsupported country_code.');
            }
            if ($this->filled('state_code') && !TaxJurisdiction::state($this->country_code, $this->state_code)->exists()) {
                $v->errors()->add('state_code', 'Unknown state_code for the given country.');
            }
            if ($this->filled('local_code')) {
                $state = TaxJurisdiction::state($this->country_code, (string) $this->state_code)->first();
                if (!$state || !TaxJurisdiction::local($this->country_code, $this->state_code, $this->local_code)->exists()) {
                    $v->errors()->add('local_code', 'Unknown local_code for the given state.');
                }
            }
        });
    }
}

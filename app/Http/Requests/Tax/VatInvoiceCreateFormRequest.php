<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;

class VatInvoiceCreateFormRequest extends FormRequest
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
            'direction'     => ['required', 'in:sale,purchase'],
            'company_id'    => ['nullable', 'integer', 'min:1'],
            'issue_date'    => ['required', 'date'],
            'country_code'  => ['required', 'string', 'size:2'],
            'state_code'    => ['nullable', 'string', 'max:10'],
            'local_code'    => ['nullable', 'string', 'max:50'],
            'tax_year'      => ['required', 'integer', 'min:2000', 'max:2100'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'lines'         => ['required', 'array', 'min:1'],
            'lines.*.description'   => ['nullable', 'string', 'max:255'],
            'lines.*.category_code' => ['required', 'string', 'max:30'], // standard|zero|exempt|...
            'lines.*.net_amount'    => ['required', 'numeric', 'min:0'],
            'lines.*.reverse_charge' => ['sometimes', 'boolean'],
            'lines.*.place_of_supply_code' => ['sometimes', 'string', 'max:10'],
            'idempotency'   => ['sometimes', 'string', 'max:80'],
        ];
    }
}

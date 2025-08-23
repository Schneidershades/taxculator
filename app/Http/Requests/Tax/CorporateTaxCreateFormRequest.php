<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\TaxJurisdiction;
use App\Models\CorporateTaxVersion;

class CorporateTaxCreateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper((string)$this->input('country_code')),
            'state_code'   => $this->filled('state_code') ? strtoupper((string)$this->input('state_code')) : null,
            'local_code'   => $this->filled('local_code') ? strtoupper((string)$this->input('local_code')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'country_code'  => ['required', 'string', 'size:2'],
            'state_code'    => ['nullable', 'string', 'max:10'],
            'local_code'    => ['nullable', 'string', 'max:50'],
            'tax_year'      => ['required', 'integer', 'min:2000', 'max:2100'],
            'company_id'    => ['nullable', 'integer', 'min:1'],
            'profit'        => ['required', 'numeric', 'min:0'], // taxable profit before losses
            'adjustments'   => ['sometimes', 'numeric'],         // optional +/-
            'idempotency'   => ['sometimes', 'string', 'max:80'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $country = TaxJurisdiction::country($this->input('country_code'))->first();
            if (!$country) {
                $v->errors()->add('country_code', 'Unknown or unsupported country_code.');
                return;
            }

            $state = $this->filled('state_code')
                ? TaxJurisdiction::state($this->input('country_code'), $this->input('state_code'))->first()
                : null;

            $local = ($state && $this->filled('local_code'))
                ? TaxJurisdiction::local($this->input('country_code'), $this->input('state_code'), $this->input('local_code'))->first()
                : null;

            $candidates = array_filter([$country, $state, $local]);

            $versions = collect($candidates)->map(
                fn($j) =>
                CorporateTaxVersion::where('tax_jurisdiction_id', $j->id)
                    ->where('tax_year', (int)$this->input('tax_year'))
                    ->active()
                    ->first()
            )->filter()->values();

            if ($versions->isEmpty()) {
                $v->errors()->add('tax_year', 'No corporate tax rules configured for this jurisdiction and year.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'profit.required' => 'Provide the company taxable profit (before loss relief).',
        ];
    }
}

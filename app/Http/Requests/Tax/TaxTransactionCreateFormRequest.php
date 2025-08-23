<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\TaxJurisdiction;
use App\Models\TaxVersion;

class TaxTransactionCreateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fresh input contract:
     * - country_code: ISO2 (e.g., NG), REQUIRED
     * - state_code: optional sub-national code (e.g., LA)
     * - local_code: optional local/municipal code (e.g., IKEJA)
     * - tax_year: required (e.g., 2025)
     * - classes: associative map of income components (short_name => amount)
     * - deductions: associative map of deduction flags (short_name => boolean)
     * - currency_code: optional ISO3 for display/formatting
     *
     * No backward compatibility shims here.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper((string) $this->input('country_code')),
            'state_code'   => $this->filled('state_code') ? strtoupper((string) $this->input('state_code')) : null,
            'local_code'   => $this->filled('local_code') ? strtoupper((string) $this->input('local_code')) : null,
        ]);

        // Normalize objects
        $classes    = $this->input('classes');
        $deductions = $this->input('deductions');

        $classes    = is_array($classes) ? array_filter($classes, fn($v) => $v !== null) : [];
        $deductions = is_array($deductions) ? $deductions : [];

        $this->merge(compact('classes', 'deductions'));
    }

    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', 'size:2'],
            'state_code'   => ['nullable', 'string', 'max:10'],
            'local_code'   => ['nullable', 'string', 'max:50'],
            'tax_year'     => ['required', 'integer', 'min:2000', 'max:2100'],

            'classes'      => ['required', 'array', 'min:1'],
            'classes.*'    => ['numeric', 'min:0'],

            'deductions'   => ['sometimes', 'array'],
            'deductions.*' => ['boolean'],

            'currency_code' => ['nullable', 'string', 'size:3'],
            'beneficiary_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Resolve most-specific jurisdictions present
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

            // Get versions for tax_year (country -> state -> local)
            $candidates = array_filter([$country, $state, $local]);

            $versions = collect($candidates)
                ->map(fn($j) => TaxVersion::where('tax_jurisdiction_id', $j->id)
                    ->where('tax_year', (int)$this->input('tax_year'))
                    ->active() // 👈 only published/frozen
                    ->first())
                ->filter()
                ->values();

            if ($versions->isEmpty()) {
                $v->errors()->add('tax_year', 'No tax rules configured for this jurisdiction and year.');
                return;
            }

            // Allowed income class keys = union across versions
            $allowedClassKeys = $versions->flatMap(function ($ver) {
                return $ver->classLinks()->with('taxClass:id,short_name')->get()
                    ->pluck('taxClass.short_name')->filter();
            })->unique()->values()->all();

            // Allowed deduction keys = union across versions
            $allowedDeductionKeys = $versions->flatMap(function ($ver) {
                return $ver->deductionRules()->with('deductionClass:id,short_name')->get()
                    ->pluck('deductionClass.short_name')->filter();
            })->unique()->values()->all();

            // Validate provided class keys exist
            foreach (array_keys($this->input('classes', [])) as $key) {
                if (!in_array($key, $allowedClassKeys, true)) {
                    $v->errors()->add("classes.$key", "Unknown income class for the selected jurisdiction/year: '$key'.");
                }
            }

            // Validate provided deduction keys exist
            foreach (array_keys($this->input('deductions', [])) as $key) {
                if (!in_array($key, $allowedDeductionKeys, true)) {
                    $v->errors()->add("deductions.$key", "Unknown deduction for the selected jurisdiction/year: '$key'.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'classes.required'  => 'Provide at least one income component.',
            'classes.*.numeric' => 'Income component values must be numeric.',
            'classes.*.min'     => 'Income component values cannot be negative.',
            'deductions.*.boolean' => 'Each deduction flag must be true or false.',
            'country_code.size' => 'country_code must be a 2-letter ISO code (e.g., NG).',
            'currency_code.size' => 'currency_code must be a 3-letter ISO code (e.g., NGN).',
        ];
    }

    public function attributes(): array
    {
        return [
            'country_code' => 'country',
            'state_code'   => 'state/province',
            'local_code'   => 'local/municipal area',
            'tax_year'     => 'tax year',
        ];
    }
}

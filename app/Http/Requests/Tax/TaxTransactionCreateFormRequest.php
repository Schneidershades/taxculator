<?php

namespace App\Http\Requests\Tax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Country;

class TaxTransactionCreateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Backward-compat normalization:
     * - taxClasses[0] -> classes
     * - taxDeductions[0] -> deductions
     */
    protected function prepareForValidation(): void
    {
        $classes = $this->input('classes');
        if (!$classes && is_array($this->input('taxClasses')) && isset($this->input('taxClasses')[0]) && is_array($this->input('taxClasses')[0])) {
            $classes = $this->input('taxClasses')[0];
        }

        $deductions = $this->input('deductions');
        if (!$deductions && is_array($this->input('taxDeductions')) && isset($this->input('taxDeductions')[0]) && is_array($this->input('taxDeductions')[0])) {
            $deductions = $this->input('taxDeductions')[0];
        }

        $classes = is_array($classes) ? array_filter($classes, fn($v) => $v !== null) : [];
        $deductions = is_array($deductions) ? $deductions : [];

        $this->merge([
            'classes'    => $classes,
            'deductions' => $deductions,
        ]);
    }

    public function rules()
    {
        return [
            'country_code' => 'required|string|size:2',         // e.g., NG
            'tax_year'     => 'required|integer|min:2000',

            'taxClasses'   => 'required|array',
            'taxClasses.*' => 'numeric|min:0',

            'taxDeductions'   => 'array',
            'taxDeductions.*' => 'boolean',

            'currency_code' => 'nullable|string|size:3',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $countryId = $this->input('country_id');
            if (!$countryId) return;

            $country = Country::with([
                'countryTaxClasses.taxClass:id,short_name',
                'countryTaxDeductions.taxDeductionClass:id,short_name',
            ])->find($countryId);

            if (!$country) return;

            // Allowed keys from DB
            $allowedClassKeys = $country->countryTaxClasses
                ->pluck('taxClass.short_name')
                ->filter()
                ->values()
                ->all();

            $allowedDeductionKeys = $country->countryTaxDeductions
                ->pluck('taxDeductionClass.short_name')
                ->filter()
                ->values()
                ->all();

            // Unknown class keys
            foreach (array_keys($this->input('classes', [])) as $key) {
                if (!in_array($key, $allowedClassKeys, true)) {
                    $v->errors()->add("classes.$key", "Unknown income class for selected country: '$key'.");
                }
            }

            // Deduction type checks: allow boolean or 0–100 numeric
            foreach ($this->input('deductions', []) as $key => $val) {
                if (!in_array($key, $allowedDeductionKeys, true)) {
                    $v->errors()->add("deductions.$key", "Unknown deduction for selected country: '$key'.");
                    continue;
                }
                if (!is_null($val) && !is_bool($val) && !(is_numeric($val) && $val >= 0 && $val <= 100)) {
                    $v->errors()->add("deductions.$key", "Deduction must be boolean or percentage (0–100).");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'classes.required' => 'Provide at least one income component.',
            'classes.*.numeric' => 'Each income component must be numeric.',
            'classes.*.min'     => 'Income component amounts cannot be negative.',
            'country_id.exists' => 'Selected country is not valid.',
            'currency.size'     => 'Currency must be a 3-letter ISO code (e.g., NGN, USD).',
        ];
    }

    public function attributes(): array
    {
        return [
            'country_id' => 'country',
            'tax_year'   => 'tax year',
        ];
    }
}

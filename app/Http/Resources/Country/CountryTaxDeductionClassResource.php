<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Tax\TaxDeductionClassResource;
use App\Http\Resources\Country\CountryClassDeductionResource;
use App\Models\TaxClass;

class CountryTaxDeductionClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'taxDeduction' => $this->taxDeductionClass->name,
            'deduction_applied_by' => $this->deduction_type,
            'value' => $this->value,
            'taxClasses' => TaxClass::whereIn('id', $this->countryTaxClasses->pluck('pivot.country_tax_class_id')->toArray())->pluck('name')->toArray()
        ];
    }
}

<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Tax\TaxDeductionClassResource;

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
        ];
    }
}

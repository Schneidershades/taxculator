<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Tax\TaxReliefClassResource;

class CountryTaxReliefClassResource extends JsonResource
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
            'taxRelief' => $this->taxReliefClass->name,
            'relief_applied_by' => $this->relief_type,
            'value' => $this->value,
            'minimum_amount' => $this->minimum_amount,
        ];
    }
}

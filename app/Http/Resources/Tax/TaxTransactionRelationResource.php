<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Tax\TaxClassResource;
use App\Http\Resources\Country\CountryTaxDeductionClassResource;
use App\Http\Resources\Country\CountryTaxReliefClassResource;

class TaxTransactionRelationResource extends JsonResource
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
            'description' => $this->description,
            'value' => $this->value,
            'applied_by' => $this->applied_by,
            $this->mergeWhen($this->description == 'taxClass', [
                'category' => new TaxClassResource($this->taxTransactionRelationable),
            ]),
            $this->mergeWhen($this->description == 'countryTaxDeductionClass', [
                'category' => new CountryTaxDeductionClassResource($this->taxTransactionRelationable),
            ]),
            $this->mergeWhen($this->description == 'countryTaxReliefClass', [
                'category' => new CountryTaxReliefClassResource($this->taxTransactionRelationable),
            ]),
            $this->mergeWhen($this->description == null , [
                'category' => $this->taxTransactionRelationable ? $this->taxTransactionRelationable : null,
            ]),
        ];
    }
}

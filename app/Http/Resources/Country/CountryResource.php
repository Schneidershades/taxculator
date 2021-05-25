<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Country\CountryTaxClassResource;
use App\Http\Resources\Country\CountryTaxReliefClassResource;
use App\Http\Resources\Country\CountryTaxDeductionResource;

class CountryResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'abbr' => $this->short_name,
            'currency' => $this->currency,
            'taxClasses' => CountryTaxClassResource::collection($this->countryTaxClasses),
            'taxReliefs' => CountryTaxReliefClassResource::collection($this->countryTaxReliefClasses),
            'taxDeductions' => CountryTaxDeductionResource::collection($this->countryTaxDeductiones),
        ];
    }
}

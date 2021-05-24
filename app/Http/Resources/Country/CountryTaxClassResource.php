<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Tax\TaxClassResource;

class CountryTaxClassResource extends JsonResource
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
            'taxClass' => $this->taxClass->name,
        ];
    }
}

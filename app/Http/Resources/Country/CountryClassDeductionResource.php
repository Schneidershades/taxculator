<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryClassDeductionResource extends JsonResource
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
            'class' => $this->country_tax_class_id,
        ];
    }
}

<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryTaxTarrifResource extends JsonResource
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
            'type' => $this->type,
            'fixed_amount' => $this->fixed_amount,
            'fixed_percentage' => $this->fixed_percentage,
            'min_range_amount' => $this->min_range_amount,
            'max_range_amount' => $this->max_range_amount,
            'min_range_percentage' => $this->min_range_percentage,
            'max_range_percentage' => $this->max_range_percentage,
            'above_fixed_amount_range' => $this->above_fixed_amount_range,
            'below_fixed_amount_range' => $this->below_fixed_amount_range,
            'above_fixed_percentage_range' => $this->above_fixed_percentage_range,
            'below_fixed_percentage_range' => $this->below_fixed_percentage_range,
        ];
    }
}

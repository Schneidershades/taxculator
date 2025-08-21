<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxVersionResource extends JsonResource
{
    public function toArray($request)
    {
        // Only use the relation if it's actually loaded; otherwise, leave it null.
        $jurisdiction = $this->resource->relationLoaded('jurisdiction')
            ? $this->jurisdiction
            : null;

        return [
            'id'             => $this->id,
            'tax_year'       => (int) $this->tax_year,
            'effective_from' => optional($this->effective_from)->toDateString(),
            'effective_to'   => optional($this->effective_to)->toDateString(),

            'jurisdiction' => [
                'id'            => optional($jurisdiction)->id,
                'level'         => optional($jurisdiction)->level,
                'country_code'  => optional($jurisdiction)->country_code,
                'state_code'    => optional($jurisdiction)->state_code,
                'local_code'    => optional($jurisdiction)->local_code,
                'name'          => optional($jurisdiction)->name,
                'currency_code' => optional($jurisdiction)->currency_code,
            ],
        ];
    }
}

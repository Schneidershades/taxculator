<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Request;
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
            'description' => $this->description,
            'applied_by'  => $this->applied_by,
            'value'       => (float) $this->value,
            'related'     => [
                'type' => $this->tax_transaction_relationable_type
                    ? class_basename($this->tax_transaction_relationable_type)
                    : null,
                'id'   => $this->tax_transaction_relationable_id,
            ],
            'created_at'  => optional($this->created_at)->toISOString(),
        ];
    }
}

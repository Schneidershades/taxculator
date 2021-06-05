<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Tax\TaxTransactionRelationResource;

class TaxTransactionResource extends JsonResource
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
            'identifier' => $this->identifier,
            'taxTranactionRelatives' => TaxTransactionRelationResource::collection($this->taxRelatives)->collection->groupBy('description'),
        ];
    }
}

<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'category' => $this->taxTransactionRelationable ? $this->taxTransactionRelationable : null,
        ];
    }
}

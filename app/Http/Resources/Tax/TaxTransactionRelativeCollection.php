<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxTransactionRelativeCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => TaxTransactionRelativeResource::collection($this->collection)
        ];
    }
}

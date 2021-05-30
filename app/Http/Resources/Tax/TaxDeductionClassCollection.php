<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxDeductionClassCollection extends ResourceCollection
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
            'data' => TaxDeductionResource::collection($this->collection)
        ];
    }
}

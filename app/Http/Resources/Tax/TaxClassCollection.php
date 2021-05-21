<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxClassCollection extends ResourceCollection
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
            'data' => TaxClassResource::collection($this->collection)
        ];
    }
}

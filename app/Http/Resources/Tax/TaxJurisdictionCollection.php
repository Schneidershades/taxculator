<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxJurisdictionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    public static function originalAttribute($index)
    {
        $attribute = [];

        return isset($attribute[$index]) ? $attribute[$index] : null;
    }

    public static function transformedAttribute($index)
    {
        $attribute = [];

        return isset($attribute[$index]) ? $attribute[$index] : null;
    }
}

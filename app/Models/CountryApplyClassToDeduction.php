<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Country\CountryApplyClassToDeductionCollection;
use App\Http\Resources\Country\CountryApplyClassToDeductionResource;

class CountryApplyClassToDeduction extends Model
{
    use HasFactory;

    public $oneItem = CountryApplyClassToDeductionResource::class;
    public $allItems = CountryApplyClassToDeductionCollection::class;
}

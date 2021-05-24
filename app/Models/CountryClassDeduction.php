<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Country\CountryClassDeductionCollection;
use App\Http\Resources\Country\CountryClassDeductionResource;

class CountryClassDeduction extends Model
{
    use HasFactory;

    public $oneItem = CountryClassDeductionResource::class;
    public $allItems = CountryClassDeductionCollection::class;
}

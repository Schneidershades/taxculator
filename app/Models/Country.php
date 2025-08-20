<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Country\CountryCollection;
use App\Http\Resources\Country\CountryResource;

class Country extends Model
{
    use HasFactory;

    public $oneItem = CountryResource::class;
    public $allItems = CountryCollection::class;
}

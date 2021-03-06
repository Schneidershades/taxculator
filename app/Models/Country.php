<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CountryTaxClass;
use App\Models\CountryTaxDeductionClass;
use App\Models\CountryTaxReliefClass;
use App\Models\CountryTaxTarrif;
use App\Http\Resources\Country\CountryCollection;
use App\Http\Resources\Country\CountryResource;

class Country extends Model
{
    use HasFactory;

    public $oneItem = CountryResource::class;
    public $allItems = CountryCollection::class;

    public function countryTaxClasses ()
    {
    	return $this->hasMany(CountryTaxClass::class);
    }

    public function countryTaxDeductiones ()
    {
    	return $this->hasMany(CountryTaxDeductionClass::class);
    }

    public function countryTaxReliefClasses ()
    {
    	return $this->hasMany(CountryTaxReliefClass::class);
    }

    public function countryTaxTarrifs ()
    {
        return $this->hasMany(CountryTaxTarrif::class);
    }
}

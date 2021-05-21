<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CountryTaxClass;
use App\Models\CountryTaxDeductionClass;
use App\Models\CountryTaxReliefClass;

class Country extends Model
{
    use HasFactory;

    public function countryTaxClasses ()
    {
    	return $this->hasMany(CountryTaxClass::class);
    }

    public function countryTaxDeductionClasses ()
    {
    	return $this->hasMany(CountryTaxDeductionClass::class);
    }

    public function countryTaxReliefClasses ()
    {
    	return $this->hasMany(CountryTaxReliefClass::class);
    }
}

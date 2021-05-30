<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\Models\TaxDeductionClass;
use App\Models\CountryTaxClass;

class CountryTaxDeductionClass extends Model
{
    use HasFactory;

    public function country()
    {
    	return $this->belongsTo(Country::class);
    }

    public function taxDeductionClass()
    {
    	return $this->belongsTo(TaxDeductionClass::class);
    }

    public function countryTaxClasses()
    {
    	return $this->belongsToMany(CountryTaxClass::class, 'country_class_deductions');
    }
}

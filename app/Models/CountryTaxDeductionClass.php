<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\Models\TaxDeduction;
use App\Models\CountryTaxClass;

class CountryTaxDeduction extends Model
{
    use HasFactory;

    public function country()
    {
    	return $this->belongsTo(Country::class);
    }

    public function taxDeductionClass()
    {
    	return $this->belongsTo(TaxDeduction::class);
    }

    public function countryTaxClasses()
    {
    	return $this->belongsToMany(CountryTaxClass::class, 'country_class_deductions');
    }
}

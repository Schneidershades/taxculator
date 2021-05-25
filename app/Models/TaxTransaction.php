<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxTransactionRelative;
use App\Models\CountryTaxDeductionsClass;
use App\Models\CountryTaxReliefClass;
use App\Models\TaxClasses;

class TaxTransaction extends Model
{
    use HasFactory;

    public function taxRelatives()
    {
    	return $this->hasMany(TaxTransactionRelative::class);
    }

    public function taxRelativeClasses()
    {
    	return $this->hasMany(TaxTransactionRelative::class)->where('tax_relationable_type', 'taxClass');
    }

    public function countryTaxDeductionClass ()
    {
    	return $this->hasMany(CountryTaxDeductionClass::class)->('tax_relationable_id', 'countryTaxDeductionClass')
    }

    public function countryTaxReliefClass ()
    {
    	return $this->hasMany(CountryTaxReliefClass::class)->('tax_relationable_id', 'countryTaxReliefClass')
    }

    public function taxClasses ()
    {
    	return $this->hasMany(TaxClasses::class)->('tax_relationable_id', 'taxClass')
    }
}

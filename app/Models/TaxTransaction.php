<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxTransactionRelative;
use App\Models\CountryTaxDeductionClass;
use App\Models\CountryTaxReliefClass;
use App\Models\TaxClasses;
use App\Http\Resources\Tax\TaxTransactionResource;
use App\Http\Resources\Tax\TaxTransactionCollection;

class TaxTransaction extends Model
{
    use HasFactory;

    public $oneItem = TaxTransactionResource::class;
    public $allItems = TaxTransactionCollection::class;

    public function taxRelatives()
    {
    	return $this->hasMany(TaxTransactionRelative::class);
    }

    public function taxTranactionRelatives()
    {
    	return $this->hasMany(TaxTransactionRelative::class)
                    ->where('tax_relationable_type', 'taxClass');
    }

    public function countryTaxDeductionClass ()
    {
    	return $this->hasMany(CountryTaxDeductionClass::class)
                    ->where('tax_relationable_id', 'countryTaxDeductionClass');
    }

    public function countryTaxReliefClass ()
    {
    	return $this->hasMany(CountryTaxReliefClass::class)
                    ->where('tax_relationable_id', 'countryTaxReliefClass');
    }

    public function taxClasses ()
    {
    	return $this->hasMany(TaxClasses::class)
                    ->where('tax_relationable_id', 'taxClass');
    }
}

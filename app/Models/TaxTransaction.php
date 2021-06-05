<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxTransactionRelation;
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
    	return $this->hasMany(TaxTransactionRelation::class);
    }

    public function taxTranactionRelatives()
    {
    	return $this->hasMany(TaxTransactionRelation::class)
                    ->where('tax_transaction_relationable_type', 'taxClass');
    }

    public function countryTaxDeductionClass ()
    {
    	return $this->hasMany(CountryTaxDeductionClass::class)
                    ->where('tax_transaction_relationable_type', 'countryTaxDeductionClass');
    }

    public function countryTaxReliefClass ()
    {
    	return $this->hasMany(CountryTaxReliefClass::class)
                    ->where('tax_transaction_relationable_type', 'countryTaxReliefClass');
    }

    public function taxClasses ()
    {
    	return $this->hasMany(TaxClasses::class)
                    ->where('tax_transaction_relationable_type', 'taxClass');
    }
}

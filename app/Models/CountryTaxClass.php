<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\Models\TaxClass;
use App\Models\TaxTransactionRelative;

class CountryTaxClass extends Model
{
    use HasFactory;

    public function country()
    {
    	return $this->belongsTo(Country::class);
    }

    public function taxClass()
    {
    	return $this->belongsTo(TaxClass::class);
    }

    public function transactionRelations()
    {
        return $this->morphMany(TaxTransactionRelative::class, 'transactionRelatable');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\Models\TaxReliefClass;

class CountryTaxReliefClass extends Model
{
    use HasFactory;

    public function country()
    {
    	return $this->belongsTo(Country::class);
    }

    public function taxReliefClass()
    {
    	return $this->belongsTo(TaxReliefClass::class);
    }
}

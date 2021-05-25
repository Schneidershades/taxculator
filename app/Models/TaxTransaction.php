<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxTransactionRelative;

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
}

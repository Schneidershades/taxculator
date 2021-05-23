<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxClass;

class TaxDeductionClass extends Model
{
    use HasFactory;

    public function taxClasses()
    {
    	return $this->belongsToMany(TaxClass::class);
    }
}

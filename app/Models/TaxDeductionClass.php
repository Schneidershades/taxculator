<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxClass;

class TaxDeductionClass extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function rules()
    {
        return $this->hasMany(TaxDeductionRule::class, 'tax_deduction_class_id');
    }
}

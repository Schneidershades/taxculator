<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxDeductionRuleTaxClass extends Model
{
    protected $casts = [
        'tax_deduction_rule_id' => 'integer',
        'tax_class_id'          => 'integer',
    ];

    public function rule()
    {
        return $this->belongsTo(TaxDeductionRule::class, 'tax_deduction_rule_id');
    }

    public function taxClass()
    {
        return $this->belongsTo(TaxClass::class, 'tax_class_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxDeductionRule extends Model
{

    protected $casts = [
        'tax_version_id'          => 'integer',
        'tax_deduction_class_id'  => 'integer',
        'value'                   => 'decimal:4',
    ];

    public function taxVersion()
    {
        return $this->belongsTo(TaxVersion::class);
    }
    public function deductionClass()
    {
        return $this->belongsTo(TaxDeductionClass::class, 'tax_deduction_class_id');
    }
    public function baseClasses()
    {
        return $this->belongsToMany(TaxClass::class, 'tax_deduction_rule_tax_classes');
    }
}

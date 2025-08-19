<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxReliefRule extends Model
{
    protected $casts = [
        'tax_version_id'     => 'integer',
        'tax_relief_class_id' => 'integer',
        'value'              => 'decimal:4',
        'minimum_amount'     => 'decimal:2',
        'maximum_amount'     => 'decimal:2',
    ];

    public function taxVersion()
    {
        return $this->belongsTo(TaxVersion::class, 'tax_version_id');
    }

    public function reliefClass()
    {
        return $this->belongsTo(TaxReliefClass::class, 'tax_relief_class_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxReliefRule extends Model
{
    use HasFactory;
    protected $guarded = [];
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

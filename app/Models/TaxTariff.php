<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;

class TaxTariff extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'tax_version_id' => 'integer',
        'bracket_min'    => 'decimal:2',
        'bracket_max'    => 'decimal:2',
        'rate_value'     => 'decimal:4',
        'ordering'       => 'integer',
    ];

    public function version()
    {
        return $this->belongsTo(TaxVersion::class, 'tax_version_id');
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('ordering');
    }
}

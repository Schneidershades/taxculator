<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxClassLink extends Model
{
    protected $casts = [
        'tax_version_id'   => 'integer',
        'tax_class_id'     => 'integer',
        'require_deduction' => 'boolean',
    ];

    public function version()
    {
        return $this->belongsTo(TaxVersion::class, 'tax_version_id');
    }

    public function taxClass()
    {
        return $this->belongsTo(TaxClass::class, 'tax_class_id');
    }
}

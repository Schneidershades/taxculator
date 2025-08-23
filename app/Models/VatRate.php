<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatRate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rate_value' => 'decimal:4',
    ];

    public function version()
    {
        return $this->belongsTo(VatVersion::class, 'vat_version_id');
    }
}

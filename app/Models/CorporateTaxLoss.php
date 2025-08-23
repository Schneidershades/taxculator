<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CorporateTaxLoss extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'company_id'       => 'integer',
        'tax_year'         => 'integer',
        'original_amount'  => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'expires_at'       => 'date',
    ];
}

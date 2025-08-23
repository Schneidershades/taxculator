<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatReturn extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'output_vat' => 'decimal:2',
        'input_vat'  => 'decimal:2',
        'net_vat'    => 'decimal:2',
        'statement'  => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApPaymentAllocation extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}


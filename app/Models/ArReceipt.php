<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArReceipt extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function allocations()
    {
        return $this->hasMany(ArReceiptAllocation::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatInvoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'issue_date'  => 'date',
        'net_total'   => 'decimal:2',
        'vat_total'   => 'decimal:2',
        'gross_total' => 'decimal:2',
        'statement'   => 'array',
    ];

    public function lines()
    {
        return $this->hasMany(VatInvoiceLine::class);
    }
}

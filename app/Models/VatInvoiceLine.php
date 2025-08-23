<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatInvoiceLine extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'net_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'vat_rate'   => 'decimal:4',
        'reverse_charge' => 'boolean',
    ];

    public function invoice()
    {
        return $this->belongsTo(VatInvoice::class, 'vat_invoice_id');
    }
}

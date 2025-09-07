<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id','account_id','description','qty','unit_price','amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}


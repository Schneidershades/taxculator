<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','external_id','number','date','due_date','customer_id','currency','total','status','journal_transaction_id',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }
}


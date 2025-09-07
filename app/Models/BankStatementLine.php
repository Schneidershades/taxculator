<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'posted_at' => 'datetime',
        'amount'    => 'decimal:2',
        'matched_at'=> 'datetime',
    ];

    public function statement()
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function matchedTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'matched_bank_transaction_id');
    }
}


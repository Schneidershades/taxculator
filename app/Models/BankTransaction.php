<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'bank_account_id', 'external_id', 'hash', 'posted_at', 'amount', 'description', 'counterparty', 'raw', 'status', 'journal_transaction_id',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'amount' => 'decimal:2',
        'raw' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journal()
    {
        return $this->belongsTo(JournalTransaction::class, 'journal_transaction_id');
    }
}


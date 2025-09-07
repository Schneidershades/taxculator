<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'transaction_id', 'account_id', 'debit', 'credit', 'occurred_at',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(JournalTransaction::class, 'transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}


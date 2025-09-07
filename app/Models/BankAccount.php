<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'provider', 'masked_number', 'currency_code', 'external_id', 'metadata', 'ledger_account_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ledgerAccount()
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'matcher_type', 'field', 'value', 'min_amount', 'max_amount', 'target_account_id', 'tax_tag', 'active', 'priority',
    ];

    protected $casts = [
        'active' => 'bool',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function targetAccount()
    {
        return $this->belongsTo(Account::class, 'target_account_id');
    }
}


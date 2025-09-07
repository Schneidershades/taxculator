<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'parent_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}


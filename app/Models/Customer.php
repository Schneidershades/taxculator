<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'external_id', 'name', 'email', 'phone', 'tax_id', 'address', 'active',
    ];

    protected $casts = [
        'address' => 'array',
        'active' => 'bool',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'external_ref', 'narrative', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function entries()
    {
        return $this->hasMany(JournalEntry::class, 'transaction_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}


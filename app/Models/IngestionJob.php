<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngestionJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'bank_account_id', 'path', 'status',
        'created_count', 'skipped_count', 'duplicates_count', 'errors_count',
        'meta', 'error_csv_path', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}


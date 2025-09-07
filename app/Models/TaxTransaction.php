<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxTransactionRelation;
use App\Http\Resources\Tax\TaxTransactionResource;
use App\Http\Resources\Tax\TaxTransactionCollection;

class TaxTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public $oneItem = TaxTransactionResource::class;
    public $allItems = TaxTransactionCollection::class;

    protected $casts = [
        'user_id' => 'integer',
        'input_snapshot'    => 'array',
        'versions_snapshot' => 'array',
        'statement'         => 'array',
        'fx_snapshot'       => 'array',   // NEW
    ];

    protected $fillable = [
        'identifier',
        'user_id',
        'input_snapshot',
        'versions_snapshot',
        'rules_hash',
        'statement',
        'idempotency_key',
        'display_currency', // NEW
        'fx_snapshot',      // NEW
        'idempotency_key',  // (already present in your DB)
    ];

    public function relations()
    {
        return $this->hasMany(TaxTransactionRelation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

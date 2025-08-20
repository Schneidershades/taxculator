<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Tax\TaxTransactionRelationResource;
use App\Http\Resources\Tax\TaxTransactionRelationCollection;

class TaxTransactionRelation extends Model
{
    use HasFactory;
    protected $guarded = [];

    public $oneItem = TaxTransactionRelationResource::class;
    public $allItems = TaxTransactionRelationCollection::class;

    protected $casts = [
        'tax_transaction_id'               => 'integer',
        'tax_transaction_relationable_id'  => 'integer',
        'value'                            => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(TaxTransaction::class, 'tax_transaction_id');
    }

    public function taxTransactionRelationable()
    {
        return $this->morphTo();
    }

    public function scopeOfType($q, string $description)
    {
        return $q->where('description', $description);
    }
}

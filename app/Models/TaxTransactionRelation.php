<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Tax\TaxTransactionRelationResource;
use App\Http\Resources\Tax\TaxTransactionRelationCollection;

class TaxTransactionRelation extends Model
{
    use HasFactory;

    public $oneItem = TaxTransactionRelationResource::class;
    public $allItems = TaxTransactionRelationCollection::class;

    public function taxTransactionRelationable()
    {
        return $this->morphTo();
    }
}

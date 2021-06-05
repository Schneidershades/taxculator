<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxTransactionRelation;

class TaxClass extends Model
{
    use HasFactory;

    public function transactionRelations()
    {
        return $this->morphMany(TaxTransactionRelation::class, 'taxTransactionRelationable');
    }
}

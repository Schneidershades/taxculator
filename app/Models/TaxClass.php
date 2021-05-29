<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TaxTransactionRelative;

class TaxClass extends Model
{
    use HasFactory;

    public function transactionRelations()
    {
        return return $this->morphMany(TaxTransactionRelative::class, 'transactionRelatable');
    }
}

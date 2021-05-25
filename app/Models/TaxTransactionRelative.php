<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxTransactionRelative extends Model
{
    use HasFactory;

    public function transactionRelatable()
    {
        return $this->morphTo();
    }
}
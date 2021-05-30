<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Tax\TaxTransactionRelativeResource;
use App\Http\Resources\Tax\TaxTransactionRelativeCollection;

class TaxTransactionRelative extends Model
{
    use HasFactory;

    public $oneItem = TaxTransactionRelativeResource::class;
    public $allItems = TaxTransactionRelativeCollection::class;

    public function transactionRelatable()
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Country\CountryClassDeductionCollection;
use App\Http\Resources\Country\CountryClassDeductionResource;
use App\Models\TaxTransactionRelative;

class CountryClassDeduction extends Model
{
    use HasFactory;

    public $oneItem = CountryClassDeductionResource::class;
    public $allItems = CountryClassDeductionCollection::class;

    public function transactionRelations()
    {
        return return $this->morphMany(TaxTransactionRelative::class, 'transactionRelatable');;
    }
}

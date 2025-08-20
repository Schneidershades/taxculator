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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Tax\TaxReliefClassResource;
use App\Http\Resources\Tax\TaxReliefClassCollection;

class TaxReliefClass extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $oneItem = TaxReliefClassResource::class;
    public $allItems = TaxReliefClassCollection::class;



    public function rules()
    {
        return $this->hasMany(TaxReliefRule::class, 'tax_relief_class_id');
    }
}

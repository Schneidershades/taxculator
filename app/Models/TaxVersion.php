<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Tax\TaxVersionResource;
use App\Http\Resources\Tax\TaxVersionCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxVersion extends Model
{

    use HasFactory;
    protected $guarded = [];

    public $oneItem = TaxVersionResource::class;
    public $allItems = TaxVersionCollection::class;

    protected $casts = [
        'tax_jurisdiction_id' => 'integer',
        'tax_year'            => 'integer',
        'effective_from'      => 'date',
        'effective_to'        => 'date',
    ];

    public function jurisdiction()
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    public function tariffs()
    {
        return $this->hasMany(TaxTariff::class);
    }

    public function classLinks()
    {
        return $this->hasMany(TaxClassLink::class);
    }

    public function deductionRules()
    {
        return $this->hasMany(TaxDeductionRule::class);
    }

    public function reliefRules()
    {
        return $this->hasMany(TaxReliefRule::class);
    }

    // Scope for a given year
    public function scopeForYear($q, int $year)
    {
        return $q->where('tax_year', $year);
    }
}

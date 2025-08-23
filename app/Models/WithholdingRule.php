<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithholdingRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function jurisdiction()
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    public function credits()
    {
        return $this->hasMany(WithholdingCredit::class);
    }
}

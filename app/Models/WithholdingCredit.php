<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithholdingCredit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'beneficiary_id'   => 'integer',
        'withholding_rule_id' => 'integer',
        'tax_year'         => 'integer',
        'base_amount'      => 'decimal:2',
        'wht_amount'       => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'consumed_at'      => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(WithholdingRule::class, 'withholding_rule_id');
    }
}

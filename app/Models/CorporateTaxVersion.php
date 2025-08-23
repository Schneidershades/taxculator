<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CorporateTaxVersion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tax_jurisdiction_id' => 'integer',
        'tax_year'            => 'integer',
        'rate_value'          => 'decimal:4',
        'minimum_tax_amount'  => 'decimal:2',
    ];

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public function scopeActive($q)
    {
        return $q->where('status', self::STATUS_PUBLISHED);
    }

    public function jurisdiction()
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }
}

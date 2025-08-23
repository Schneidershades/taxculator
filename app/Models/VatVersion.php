<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VatVersion extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public function scopeActive($q)
    {
        return $q->where('status', self::STATUS_PUBLISHED);
    }

    public function jurisdiction()
    {
        return $this->belongsTo(TaxJurisdiction::class, 'tax_jurisdiction_id');
    }

    public function rates()
    {
        return $this->hasMany(VatRate::class);
    }
}

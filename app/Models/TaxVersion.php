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

    // Status constants
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FROZEN    = 'frozen';
    public const STATUS_ARCHIVED  = 'archived';


    public $oneItem = TaxVersionResource::class;
    public $allItems = TaxVersionCollection::class;

    protected $casts = [
        'tax_jurisdiction_id' => 'integer',
        'tax_year'            => 'integer',
        'effective_from'      => 'date',
        'effective_to'        => 'date',
        'published_at' => 'datetime',
        'frozen_at'    => 'datetime',
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

    public function contributionRules()
    {
        return $this->hasMany(ContributionRule::class, 'tax_version_id');
    }

    // Scope for a given year
    public function scopeForYear($q, int $year)
    {
        return $q->where('tax_year', $year);
    }

    public function scopeActive($q)
    {
        // only versions valid for API/calculations
        return $q->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_FROZEN]);
    }

    public function publish(): void
    {
        $this->update([
            'status'       => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function freeze(): void
    {
        $this->update([
            'status'    => self::STATUS_FROZEN,
            'frozen_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status'       => self::STATUS_DRAFT,
            'published_at' => null,
            'frozen_at'    => null,
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }
}

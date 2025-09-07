<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FxRate extends Model
{
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'as_of_date',
        'source',
    ];

    protected $casts = [
        'rate'       => 'decimal:8',
        'as_of_date' => 'date',
    ];

    public function scopePair($q, string $base, string $quote)
    {
        return $q->where('base_currency', strtoupper($base))
            ->where('quote_currency', strtoupper($quote));
    }

    public function scopeOnOrBefore($q, ?string $date)
    {
        if (!$date) return $q;
        return $q->whereDate('as_of_date', '<=', $date);
    }
}

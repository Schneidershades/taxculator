<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxJurisdiction extends Model
{
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    public function versions()
    {
        return $this->hasMany(TaxVersion::class);
    }

    // helpers
    public function scopeCountry($q, $code)
    {
        return $q->where('level', 'country')->where('country_code', $code);
    }
    public function scopeState($q, $country, $state)
    {
        return $q->where('level', 'state')->where('country_code', $country)->where('state_code', $state);
    }
    public function scopeLocal($q, $country, $state, $local)
    {
        return $q->where('level', 'local')->where('country_code', $country)->where('state_code', $state)->where('local_code', $local);
    }
}

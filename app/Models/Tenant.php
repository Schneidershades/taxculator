<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'country_code',
        'state_code',
        'tax_ids',
        'base_currency',
        'vat_registration_date',
        'cit_registration_date',
        'default_vat_rate',
    ];

    protected $casts = [
        'tax_ids' => 'array',
        'vat_registration_date' => 'date',
        'cit_registration_date' => 'date',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CorporateTaxTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'company_id'      => 'integer',
        'input_snapshot'  => 'array',
        'version_snapshot' => 'array',
        'statement'       => 'array',
    ];
}

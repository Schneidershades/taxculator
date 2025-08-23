<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContributionRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'employee_rate'  => 'decimal:4',
        'employer_rate'  => 'decimal:4',
        'employee_cap'   => 'decimal:2',
        'employer_cap'   => 'decimal:2',
        'employee_floor' => 'decimal:2',
        'employer_floor' => 'decimal:2',
    ];

    public function version()
    {
        return $this->belongsTo(TaxVersion::class, 'tax_version_id');
    }

    public function baseClasses()
    {
        return $this->belongsToMany(TaxClass::class, 'contribution_rule_base_classes');
    }
}

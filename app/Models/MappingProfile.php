<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappingProfile extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'name', 'entity', 'mapping', 'sample_header'];

    protected $casts = [
        'mapping' => 'array',
        'sample_header' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

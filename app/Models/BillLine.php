<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id','account_id','description','qty','unit_price','amount',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
}


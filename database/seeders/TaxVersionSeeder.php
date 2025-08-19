<?php

namespace Database\Seeders;

use App\Models\TaxVersion;
use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxVersionSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;

        foreach (TaxJurisdiction::all() as $j) {
            TaxVersion::updateOrCreate(
                ['tax_jurisdiction_id' => $j->id, 'tax_year' => $year],
                ['effective_from' => "$year-01-01", 'effective_to' => null]
            );
        }
    }
}

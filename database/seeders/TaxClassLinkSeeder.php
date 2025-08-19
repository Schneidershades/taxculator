<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use App\Models\TaxVersion;
use App\Models\TaxClassLink;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxClassLinkSeeder extends Seeder
{
    public function run()
    {
        $year = 2025;
        $versions = TaxVersion::with('jurisdiction')->where('tax_year', $year)->get();

        $classIds = TaxClass::pluck('id', 'short_name'); // ['basic_salary'=>1, ...]

        foreach ($versions as $v) {
            foreach ($classIds as $short => $id) {
                TaxClassLink::updateOrCreate(
                    ['tax_version_id' => $v->id, 'tax_class_id' => $id],
                    ['require_deduction' => in_array($short, ['housing', 'clothing'])] // example flag
                );
            }
        }
    }
}

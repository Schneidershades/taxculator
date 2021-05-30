<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CountryTaxTarrif;

class CountryTarrifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $class = CountryTaxTarrif::create([
            'country_id' => 1,
            'type' => 'fixed',
            'fixed_amount' => 300000,
            'fixed_percentage' => 7,
            'min_range_amount' => 0,
            'max_range_amount' => 0,
            'min_range_percentage' => 0,
            'max_range_percentage' => 0,
            'above_fixed_amount_range' => false,
            'below_fixed_amount_range' => false,
            'above_fixed_percentage_range' => false,
            'below_fixed_percentage_range' => false,
            'ordering_id' => 1,
        ]);

        $class = CountryTaxTarrif::create([
            'country_id' => 1,
            'type' => 'fixed',
            'fixed_amount' => 300000,
            'fixed_percentage' => 11,
            'min_range_amount' => 0,
            'max_range_amount' => 0,
            'min_range_percentage' => 0,
            'max_range_percentage' => 0,
            'above_fixed_amount_range' => false,
            'below_fixed_amount_range' => false,
            'above_fixed_percentage_range' => false,
            'below_fixed_percentage_range' => false,
            'ordering_id' => 2,
        ]);

        $class = CountryTaxTarrif::create([
            'country_id' => 1,
            'type' => 'fixed',
            'fixed_amount' => 500000,
            'fixed_percentage' => 15,
            'min_range_amount' => 0,
            'max_range_amount' => 0,
            'min_range_percentage' => 0,
            'max_range_percentage' => 0,
            'above_fixed_amount_range' => false,
            'below_fixed_amount_range' => false,
            'above_fixed_percentage_range' => false,
            'below_fixed_percentage_range' => false,
            'ordering_id' => 3,
        ]);

        $class = CountryTaxTarrif::create([
            'country_id' => 1,
            'type' => 'fixed',
            'fixed_amount' => 500000,
            'fixed_percentage' => 19,
            'min_range_amount' => 0,
            'max_range_amount' => 0,
            'min_range_percentage' => 0,
            'max_range_percentage' => 0,
            'above_fixed_amount_range' => false,
            'below_fixed_amount_range' => false,
            'above_fixed_percentage_range' => false,
            'below_fixed_percentage_range' => false,
            'ordering_id' => 4,
        ]);

        $class = CountryTaxTarrif::create([
            'country_id' => 1,
            'type' => 'fixed',
            'fixed_amount' => 1600000,
            'fixed_percentage' => 21,
            'min_range_amount' => 0,
            'max_range_amount' => 0,
            'min_range_percentage' => 0,
            'max_range_percentage' => 0,
            'above_fixed_amount_range' => false,
            'below_fixed_amount_range' => false,
            'above_fixed_percentage_range' => false,
            'below_fixed_percentage_range' => false,
            'ordering_id' => 5,
        ]);

        $class = CountryTaxTarrif::create([
            'country_id' => 1,
            'type' => 'fixed',
            'fixed_amount' => 0.00,
            'fixed_percentage' => 24,
            'min_range_amount' => 0,
            'max_range_amount' => 0,
            'min_range_percentage' => 0,
            'max_range_percentage' => 0,
            'above_fixed_amount_range' => false,
            'below_fixed_amount_range' => false,
            'above_fixed_percentage_range' => false,
            'below_fixed_percentage_range' => false,
            'ordering_id' => 6,
        ]);
    }
}
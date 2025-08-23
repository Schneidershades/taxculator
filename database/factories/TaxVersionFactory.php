<?php

namespace Database\Factories;

use App\Models\TaxJurisdiction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxVersion>
 */
class TaxVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'tax_jurisdiction_id' => TaxJurisdiction::factory(),
            'tax_year' => fake()->numberBetween(2020, 2030),
            'effective_from' => '2025-01-01'
        ];
    }
}

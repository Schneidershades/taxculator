<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxJurisdiction>
 */
class TaxJurisdictionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return ['level' => 'country', 'name' => $this->faker->country(), 'country_code' => 'NG', 'currency_code' => 'NGN'];
    }
}

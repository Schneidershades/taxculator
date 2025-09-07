<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FxRate;

class FxRateSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->toDateString();

        $pairs = [
            // dev/test illustrative values
            ['NGN', 'USD', 0.00070, $today],
            ['USD', 'NGN', 1500.00000000, $today],
            ['NGN', 'EUR', 0.00060, $today],
            ['EUR', 'NGN', 1666.66666667, $today],
        ];

        foreach ($pairs as [$base, $quote, $rate, $date]) {
            FxRate::updateOrCreate(
                ['base_currency' => $base, 'quote_currency' => $quote, 'as_of_date' => $date],
                ['rate' => $rate, 'source' => 'seed']
            );
        }
    }
}

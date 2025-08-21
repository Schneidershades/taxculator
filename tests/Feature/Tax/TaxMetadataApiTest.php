<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\TaxBootstrapSeeder;
use Illuminate\Testing\Fluent\AssertableJson;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TaxBootstrapSeeder::class);
});

test('GET /api/v1/tax/versions returns versions for country and sub-nationals', function () {
    $this->getJson('/api/v1/tax/versions?country_code=NG&state_code=LA&local_code=IKEJA')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [['id', 'tax_year', 'effective_from', 'jurisdiction' => ['level', 'country_code']]],
        ]);
});

test('GET /api/v1/tax/tariffs requires tax_year', function () {
    $this->getJson('/api/v1/tax/tariffs?country_code=NG')
        ->assertStatus(422);
});

test('GET /api/v1/tax/tariffs returns country/state/local brackets tagged by level', function () {
    $this->getJson('/api/v1/tax/tariffs?country_code=NG&state_code=LA&local_code=IKEJA&tax_year=2025')
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [['id', 'level', 'ordering', 'bracket_min', 'rate_type', 'rate_value']],
        ])
        ->assertJson(function (AssertableJson $json) {
            $json->where('success', true)
                ->whereType('message', 'string')
                ->has(
                    'data',
                    fn($items) =>
                    $items->each(
                        fn($t) =>
                        $t->where('level', fn($lvl) => in_array($lvl, ['country', 'state', 'local'], true))
                            ->etc()
                    )
                )
                ->etc();
        });
});

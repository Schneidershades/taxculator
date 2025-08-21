<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Country\CountryController;
use App\Http\Controllers\Api\V1\Tax\TaxMetadataController;
use App\Http\Controllers\Api\V1\Tax\TaxCalculationController;
use App\Http\Controllers\Api\V1\Tax\TaxTransactionController;

Route::prefix('v1')->group(function () {
	Route::group(['prefix' => 'tax'], function () {
		Route::resource('countries', CountryController::class);
		Route::resource('tax-transactions', TaxTransactionController::class);
		Route::resource('tax-calculations', TaxCalculationController::class);
		Route::get('transactions/{id}', [TaxTransactionController::class, 'show']);
		Route::get('versions', [TaxMetadataController::class, 'versions']);
		Route::get('tariffs',  [TaxMetadataController::class, 'tariffs']);
	});
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Vat\VatController;
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
		Route::get('tax-transactions/{id}/statement', [TaxTransactionController::class, 'statement']);
		Route::get('tax-transactions/{id}/statement.pdf', [TaxTransactionController::class, 'statementPdf']);
	});

	// Route::prefix('cit')->group(function () {
	// });

	Route::middleware('throttle:tax-calc')->group(function () {
		Route::prefix('cit')->group(function () {
			Route::post('calculate', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'preview']);
			Route::post('transactions', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'store']);
		});
	});

	Route::prefix('vat')->group(function () {
		Route::post('invoices', [VatController::class, 'createInvoice']);
		Route::get('returns/preview', [VatController::class, 'previewReturn']);
		Route::post('returns', [VatController::class, 'fileReturn']);
	});
});

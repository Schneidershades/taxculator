<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
	Route::group(['namespace' => 'Api'], function(){
		Route::resource('countries', 'Country\CountryController');
		Route::resource('tax-transactions', 'Tax\TaxTransactionController');
	});
});


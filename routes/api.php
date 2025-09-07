<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Vat\VatController;
use App\Http\Controllers\Api\V1\Country\CountryController;
use App\Http\Controllers\Api\V1\Tenant\TenantController as ApiTenantController;
use App\Http\Controllers\Api\V1\Reports\ReportsController;
use App\Http\Controllers\Api\V1\Tax\TaxMetadataController;
use App\Http\Controllers\Api\V1\Tax\TaxCalculationController;
use App\Http\Controllers\Api\V1\Tax\TaxTransactionController;
use App\Http\Controllers\Api\V1\Ingestion\CsvIngestionController;
use App\Http\Controllers\Api\V1\Ingestion\IngestionJobsController;
use App\Http\Controllers\Api\V1\Transactions\BankTransactionsController;
use App\Http\Controllers\Api\V1\SummaryController;
use App\Http\Controllers\Api\V1\Rules\RulesController;
use App\Http\Controllers\Api\V1\Ingestion\MappingProfilesController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Imports\ImportController;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('auth:sanctum');
    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify')->middleware('signed');
    Route::prefix('2fa')->middleware('auth:sanctum')->group(function () {
        Route::post('setup', [TwoFactorController::class, 'setup']);
        Route::post('verify', [TwoFactorController::class, 'verify']);
        Route::get('backup-codes/download', [TwoFactorController::class, 'downloadRecoveryCodes']);
    });
	Route::group(['prefix' => 'tax'], function () {
		Route::resource('countries', CountryController::class);
		Route::resource('tax-transactions', TaxTransactionController::class);
		Route::resource('tax-calculations', TaxCalculationController::class);
		Route::get('transactions/{id}', [TaxTransactionController::class, 'show']);
		Route::get('versions', [TaxMetadataController::class, 'versions']);
		Route::get('tariffs',  [TaxMetadataController::class, 'tariffs']);
		Route::get('tax-transactions/{id}/statement', [TaxTransactionController::class, 'statement']);
		Route::get('tax-transactions/{id}/statement.pdf', [TaxTransactionController::class, 'statementPdf']);
		Route::get('tax-transactions/{id}/pack', [TaxTransactionController::class, 'packLinks'])->middleware(['auth:sanctum','verified.api']);
	});

	// Route::prefix('cit')->group(function () {
	// });

	Route::middleware('throttle:tax-calc')->group(function () {
		Route::prefix('cit')->group(function () {
			Route::get('transactions', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'index']);
			Route::post('calculate', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'preview']);
			Route::post('transactions', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'store']);
			Route::get('transactions/{id}/pack', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'packLinks'])->middleware(['auth:sanctum','verified.api']);
		});
	});

    Route::prefix('vat')->group(function () {
        Route::post('invoices', [VatController::class, 'createInvoice']);
        Route::get('invoices', [VatController::class, 'index']);
        Route::get('returns/preview', [VatController::class, 'previewReturn']);
        Route::post('returns', [VatController::class, 'fileReturn']);
    });

    Route::prefix('ingest')->middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::post('csv', [CsvIngestionController::class, 'store']);
        Route::get('mappings', [MappingProfilesController::class, 'index']);
        Route::post('mappings', [MappingProfilesController::class, 'store']);
        Route::get('jobs', [IngestionJobsController::class, 'index']);
        Route::get('jobs/{id}', [IngestionJobsController::class, 'show']);
        Route::get('jobs/{id}/errors.csv', [IngestionJobsController::class, 'errorsCsv']);
    });

    // Transactions (bank) listing & categorization
    Route::prefix('transactions')->middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::get('', [BankTransactionsController::class, 'index']);
        Route::put('{id}', [BankTransactionsController::class, 'update']);
        Route::put('bulk', [BankTransactionsController::class, 'bulk']);
    });

    // Summary aggregates
    Route::get('summary', SummaryController::class)->middleware(['auth:sanctum','verified.api']);

    // Meta routes (alias for countries)
    Route::get('meta/countries', [CountryController::class, 'index']);

    // Tenants
    Route::post('tenants', [ApiTenantController::class, 'store'])->middleware(['auth:sanctum','verified.api','role:owner']);
    Route::put('tenants/{id}/tax-settings', [ApiTenantController::class, 'updateTaxSettings'])->middleware(['auth:sanctum','verified.api','role:owner']);

    // Rules
    Route::middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::get('rules', [RulesController::class, 'index']);
        Route::post('rules', [RulesController::class, 'store']);
        Route::put('rules/{id}', [RulesController::class, 'update']);
        Route::delete('rules/{id}', [RulesController::class, 'destroy']);
        Route::post('rules/apply', [RulesController::class, 'apply']);
    });

    // Reports (auth + verified)
    Route::middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::get('reports/pnl', [ReportsController::class, 'pnl']);
        Route::get('reports/cashflow', [ReportsController::class, 'cashflow']);
        Route::get('reports/trial-balance', [ReportsController::class, 'trialBalance']);
        Route::get('reports/balance-sheet', [ReportsController::class, 'balanceSheet']);
        Route::get('reports/gl', [ReportsController::class, 'gl']);
        Route::get('reports/ar-aging', [\App\Http\Controllers\Api\V1\Reports\ArApAgingController::class, 'ar']);
        Route::get('reports/ap-aging', [\App\Http\Controllers\Api\V1\Reports\ArApAgingController::class, 'ap']);

        // Signed export links generator
        Route::get('reports/pnl/export', [ReportsController::class, 'pnlExportLinks']);
        Route::get('reports/cashflow/export', [ReportsController::class, 'cashflowExportLinks']);
        Route::get('reports/trial-balance/export', [ReportsController::class, 'trialBalanceExportLinks']);
        Route::get('reports/balance-sheet/export', [ReportsController::class, 'balanceSheetExportLinks']);
        Route::get('reports/gl/export', [ReportsController::class, 'glExportLinks']);
        Route::get('reports/reconciliation/export', [ReportsController::class, 'reconciliationExportLinks']);
        Route::get('reports/reconciliation/export', [ReportsController::class, 'reconciliationExportLinks']);
    });

    // Signed download routes (no auth; short-lived via signature) include {tenant} for tenancy
    Route::get('reports/{tenant}/pnl.csv', [ReportsController::class, 'downloadPnlCsv'])->name('rep.pnl.csv')->middleware('signed');
    Route::get('reports/{tenant}/pnl.pdf', [ReportsController::class, 'downloadPnlPdf'])->name('rep.pnl.pdf')->middleware('signed');
    Route::get('reports/{tenant}/cashflow.csv', [ReportsController::class, 'downloadCashflowCsv'])->name('rep.cashflow.csv')->middleware('signed');
    Route::get('reports/{tenant}/cashflow.pdf', [ReportsController::class, 'downloadCashflowPdf'])->name('rep.cashflow.pdf')->middleware('signed');
    Route::get('reports/{tenant}/trial-balance.csv', [ReportsController::class, 'downloadTrialBalanceCsv'])->name('rep.tb.csv')->middleware('signed');
    Route::get('reports/{tenant}/trial-balance.pdf', [ReportsController::class, 'downloadTrialBalancePdf'])->name('rep.tb.pdf')->middleware('signed');
    Route::get('reports/{tenant}/balance-sheet.csv', [ReportsController::class, 'downloadBalanceSheetCsv'])->name('rep.bs.csv')->middleware('signed');
    Route::get('reports/{tenant}/balance-sheet.pdf', [ReportsController::class, 'downloadBalanceSheetPdf'])->name('rep.bs.pdf')->middleware('signed');
    Route::get('reports/{tenant}/gl.csv', [ReportsController::class, 'downloadGlCsv'])->name('rep.gl.csv')->middleware('signed');
    Route::get('reports/{tenant}/gl.pdf', [ReportsController::class, 'downloadGlPdf'])->name('rep.gl.pdf')->middleware('signed');
    Route::get('reports/{tenant}/reconciliation.csv', [ReportsController::class, 'downloadReconciliationCsv'])->name('rep.recon.csv')->middleware('signed');
    Route::get('reports/{tenant}/reconciliation.pdf', [ReportsController::class, 'downloadReconciliationPdf'])->name('rep.recon.pdf')->middleware('signed');

    // Imports (generic) for accounting master data
    Route::prefix('imports')->middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::post('{entity}', [ImportController::class, 'store']);
        Route::get('templates/{entity}.csv', [ImportController::class, 'template']);
    });

    // Accounting periods (owner only)
    Route::prefix('periods')->middleware(['auth:sanctum','verified.api','role:owner'])->group(function () {
        Route::get('', [\App\Http\Controllers\Api\V1\Periods\PeriodsController::class, 'index']);
        Route::post('close', [\App\Http\Controllers\Api\V1\Periods\PeriodsController::class, 'close']);
        Route::post('open', [\App\Http\Controllers\Api\V1\Periods\PeriodsController::class, 'open']);
    });

    // Bank statements & reconciliation
    Route::prefix('bank')->middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::get('statements', [\App\Http\Controllers\Api\V1\Bank\BankStatementsController::class, 'index']);
        Route::get('statements/{id}', [\App\Http\Controllers\Api\V1\Bank\BankStatementsController::class, 'show']);
        Route::post('statements', [\App\Http\Controllers\Api\V1\Bank\BankStatementsController::class, 'store']);
        Route::post('reconcile', [\App\Http\Controllers\Api\V1\Bank\BankReconciliationController::class, 'reconcile']);
    });

    // AR/AP receipts & payments
    Route::prefix('ar')->middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::post('receipts', [\App\Http\Controllers\Api\V1\Ar\ReceiptsController::class, 'store']);
    });
    Route::prefix('ap')->middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::post('payments', [\App\Http\Controllers\Api\V1\Ap\PaymentsController::class, 'store']);
    });
});

// Signed downloads for CIT/PIT packs (outside auth; signed-only)
Route::get('api/v1/cit/transactions/{id}/pack.pdf', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'downloadPackPdf'])->name('cit.pack.pdf')->middleware('signed');
Route::get('api/v1/cit/transactions/{id}/pack.csv', [\App\Http\Controllers\Api\V1\Cit\CorporateTaxController::class, 'downloadPackCsv'])->name('cit.pack.csv')->middleware('signed');
Route::get('api/v1/tax/tax-transactions/{id}/pack.pdf', [TaxTransactionController::class, 'downloadPackPdf'])->name('pit.pack.pdf')->middleware('signed');
Route::get('api/v1/tax/tax-transactions/{id}/pack.csv', [TaxTransactionController::class, 'downloadPackCsv'])->name('pit.pack.csv')->middleware('signed');

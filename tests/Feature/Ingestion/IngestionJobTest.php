<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\IngestionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(TestCase::class, RefreshDatabase::class);

test('CSV ingestion creates job and error CSV is downloadable', function () {
    // Tenant, user and basic accounts (bank 1100, cash 1000, suspense 9999)
    $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'country_code' => 'NG']);
    $user = User::factory()->create(['email_verified_at' => now(), 'tenant_id' => $tenant->id]);
    $token = $user->createToken('test')->plainTextToken;
    Account::create(['tenant_id' => $tenant->id, 'code' => '1100', 'name' => 'Bank', 'type' => 'asset']);
    Account::create(['tenant_id' => $tenant->id, 'code' => '9999', 'name' => 'Suspense', 'type' => 'asset']);
    $bank = BankAccount::create(['tenant_id' => $tenant->id, 'name' => 'CSV Upload', 'provider' => 'csv', 'currency_code' => 'NGN']);

    // Prepare CSV with one good and one bad row
    $content = "date,amount,description\n2025-01-05,1000,OK Row\nINVALID_DATE,abc,Bad Row\n";
    $file = UploadedFile::fake()->createWithContent('bank.csv', $content);

    // Upload
    $res = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Tenant' => $tenant->slug,
    ])->post('/api/v1/ingest/csv', [
        'file' => $file,
        'bank_account_id' => $bank->id,
    ])->assertAccepted()->json();

    $jobId = $res['job_id'];
    $job = IngestionJob::find($jobId);

    // Run the job synchronously
    app(\App\Jobs\ParseCsvUpload::class, [
        'tenantId' => $tenant->id,
        'bankAccountId' => $bank->id,
        'path' => $job->path,
        'ingestionJobId' => $jobId,
    ])->handle(app(\App\Services\Ledger\LedgerService::class));

    // Poll job
    $poll = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Tenant' => $tenant->slug,
    ])->getJson("/api/v1/ingest/jobs/{$jobId}")->assertOk()->json('data');

    expect($poll['status'])->toEqual('completed');
    expect($poll['created_count'])->toBeGreaterThan(0);
    expect($poll['errors_count'])->toBeGreaterThan(0);
    expect($poll['error_csv_path'])->not->toBeNull();

    // Download error CSV
    $csv = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-Tenant' => $tenant->slug,
    ])->get("/api/v1/ingest/jobs/{$jobId}/errors.csv")->assertOk();
    $csv->assertHeader('content-type', 'text/csv');
    expect($csv->getContent())->toContain('Invalid date');
});


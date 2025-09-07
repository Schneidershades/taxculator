<?php

namespace App\Http\Controllers\Api\V1\Ingestion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ingestion\CsvUploadRequest;
use App\Jobs\ParseCsvUpload;
use App\Models\IngestionJob;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Tenant;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @group Ingestion
 * Upload CSV files for background ingestion. Provide X-Tenant header.
 */
class CsvIngestionController extends Controller
{
    public function store(CsvUploadRequest $request): JsonResponse
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam file file required The CSV file to upload.
        // @bodyParam bank_account_id integer The target bank account id. Example: 1
        // @bodyParam mapping_profile_id integer The saved mapping profile to use. Example: 2
        /** @var Tenant|null $tenant */
        $tenant = Tenancy::current();
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not identified. Include X-Tenant header.'], 422);
        }

        $bank = $this->resolveBankAccount($tenant, (int) $request->input('bank_account_id')); 

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $file->storeAs("csv_uploads/{$tenant->id}", uniqid('upload_', true).'.csv');

        // create ingestion job row
        $job = IngestionJob::create([
            'tenant_id'      => $tenant->id,
            'bank_account_id'=> $bank->id,
            'path'           => $path,
            'status'         => 'queued',
            'meta'           => [
                'original_name' => $file->getClientOriginalName(),
                'size'          => $file->getSize(),
                'mime'          => $file->getClientMimeType(),
                'mapping_profile_id' => $request->input('mapping_profile_id'),
            ],
        ]);

        ParseCsvUpload::dispatch($tenant->id, $bank->id, $path, $job->id);

        return response()->json([
            'message' => 'Upload received. Parsing started.',
            'bank_account_id' => $bank->id,
            'path' => $path,
            'job_id' => $job->id,
        ], 202);
    }

    protected function resolveBankAccount(Tenant $tenant, ?int $bankAccountId): BankAccount
    {
        if ($bankAccountId) {
            $bank = BankAccount::where('tenant_id', $tenant->id)->findOrFail($bankAccountId);
        } else {
            $bank = BankAccount::firstOrCreate([
                'tenant_id' => $tenant->id,
                'provider' => 'csv',
                'name' => 'CSV Upload',
            ], [
                'currency_code' => $tenant->base_currency ?? 'NGN',
            ]);
        }

        // Ensure a ledger asset account is linked (Bank 1100 or Cash 1000 fallback)
        if (!$bank->ledger_account_id) {
            $ledger = Account::where('tenant_id', $tenant->id)->where('code', '1100')->first()
                ?: Account::where('tenant_id', $tenant->id)->where('code', '1000')->first();
            if ($ledger) {
                $bank->ledger_account_id = $ledger->id;
                $bank->save();
            }
        }

        return $bank;
    }
}

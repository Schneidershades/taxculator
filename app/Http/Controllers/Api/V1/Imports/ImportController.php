<?php

namespace App\Http\Controllers\Api\V1\Imports;

use App\Http\Controllers\Controller;
use App\Models\IngestionJob;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    private const ENTITIES = ['accounts','customers','vendors','invoices','bills','journals'];

    /**
     * POST /api/v1/imports/{entity}
     * Multipart: file, mapping_profile_id?
     */
    public function store(Request $request, string $entity): JsonResponse
    {
        $entity = strtolower($entity);
        if (!in_array($entity, self::ENTITIES, true)) {
            return $this->respondError('Unsupported entity for import.', 422);
        }

        $request->validate([
            'file' => ['required','file','mimes:csv,txt','max:10240'],
            'mapping_profile_id' => ['nullable','integer','exists:mapping_profiles,id'],
        ]);

        $tenant = Tenancy::current();
        if (!$tenant) {
            return $this->respondError('Tenant not identified. Include X-Tenant header.', 422);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $dir = "imports/{$tenant->id}/{$entity}";
        $path = $file->storeAs($dir, uniqid($entity.'_', true).'.csv');

        $job = IngestionJob::create([
            'tenant_id'       => $tenant->id,
            'path'            => $path,
            'status'          => 'queued',
            'meta'            => [
                'import_type'        => $entity,
                'mapping_profile_id' => $request->input('mapping_profile_id'),
                'original_name'      => $file->getClientOriginalName(),
                'size'               => $file->getSize(),
            ],
        ]);

        \App\Jobs\ParseEntityImport::dispatch($tenant->id, $entity, $path, $job->id);

        return $this->respondSuccess([
            'message' => 'Import received. Processing started.',
            'job_id'  => $job->id,
        ], 202);
    }

    /** Download CSV template for an entity (headers only). */
    public function template(string $entity)
    {
        $entity = strtolower($entity);
        if (!in_array($entity, self::ENTITIES, true)) {
            return $this->respondError('Unsupported entity template.', 404);
        }

        $headers = match ($entity) {
            'accounts'  => ['code','name','type','parent_code','is_active'],
            'customers' => ['external_id','name','email','phone','tax_id','address_line1','address_line2','city','state','postal_code','country','active'],
            'vendors'   => ['external_id','name','email','phone','tax_id','address_line1','address_line2','city','state','postal_code','country','active'],
            'invoices'  => ['number','date','due_date','customer_external_id','customer_name','currency','account_code','description','qty','unit_price'],
            'bills'     => ['number','date','due_date','vendor_external_id','vendor_name','currency','account_code','description','qty','unit_price'],
            'journals'  => ['date','memo','account_code','debit','credit','contra_account_code'],
        };

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers);
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_'.$entity.'.csv"',
        ]);
    }
}

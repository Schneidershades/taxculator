<?php

namespace App\Http\Controllers\Api\V1\Ingestion;

use App\Http\Controllers\Controller;
use App\Models\IngestionJob;
use App\Support\Tenancy;
use Illuminate\Http\Request;

/**
 * @group Ingestion
 * Check CSV ingestion job status and download error CSV.
 */
class IngestionJobsController extends Controller
{
    public function index(Request $request)
    {
        $tenant = Tenancy::current();
        $q = IngestionJob::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->orderByDesc('id');

        return $this->showAll($q->paginate(20));
    }

    public function show(int $id)
    {
        $tenant = Tenancy::current();
        $job = IngestionJob::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->findOrFail($id);
        return $this->showOne($job);
    }

    public function errorsCsv(int $id)
    {
        $tenant = Tenancy::current();
        $job = IngestionJob::query()
            ->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))
            ->findOrFail($id);

        if (!$job->error_csv_path || !\Storage::exists($job->error_csv_path)) {
            return $this->respondError('No error CSV available for this job.', 404);
        }

        $content = \Storage::get($job->error_csv_path);
        $filename = 'errors_job_'.$job->id.'.csv';
        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Ingestion;

use App\Http\Controllers\Controller;
use App\Models\MappingProfile;
use App\Support\Tenancy;
use Illuminate\Http\Request;

/**
 * @group Ingestion
 * Manage CSV mapping profiles for header-to-field mapping.
 */
class MappingProfilesController extends Controller
{
    public function index()
    {
        $tenant = Tenancy::current();
        $q = MappingProfile::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id));
        return $this->showAll($q->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        // @authenticated
        // @header X-Tenant string required Tenant slug for scoping.
        // @bodyParam name string required Profile name. Example: Zenith CSV v1
        // @bodyParam mapping object required The field mapping object.
        // @bodyParam mapping.date string required Name of the date column in the CSV. Example: Txn Date
        // @bodyParam mapping.amount string required Name of the amount column in the CSV. Example: Amount
        // @bodyParam mapping.description string Name of the description column. Example: Narration
        // @bodyParam mapping.counterparty string Name of the counterparty column. Example: Beneficiary
        $tenant = Tenancy::current();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'mapping' => ['required', 'array'],
            'mapping.date' => ['required', 'string'],
            'mapping.amount' => ['required', 'string'],
            'mapping.description' => ['nullable', 'string'],
            'mapping.counterparty' => ['nullable', 'string'],
            'sample_header' => ['nullable', 'array'],
        ]);

        $mp = MappingProfile::create([
            'tenant_id' => $tenant?->id,
            'name' => $data['name'],
            'mapping' => $data['mapping'],
            'sample_header' => $data['sample_header'] ?? null,
        ]);

        return $this->respondSuccess(['message' => 'Mapping profile saved.', 'data' => $mp], 201);
    }
}

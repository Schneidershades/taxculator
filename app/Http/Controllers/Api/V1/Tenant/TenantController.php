<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * @group Settings
 * Manage tenants and tax settings (owner only).
 */
class TenantController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'string', 'max:190', 'unique:tenants,slug'],
            'country_code' => ['required', 'string', 'size:2'],
            'state_code' => ['nullable', 'string', 'max:10'],
            'base_currency' => ['nullable', 'string', 'size:3'],
        ]);

        $t = Tenant::create($data);
        return $this->respondSuccess(['message' => 'Tenant created.', 'data' => $t], 201);
    }

    public function updateTaxSettings(Request $request, int $id)
    {
        $data = $request->validate([
            'tax_ids' => ['nullable', 'array'],
            'tax_ids.*' => ['string', 'max:190'],
            'vat_registration_date' => ['nullable', 'date'],
            'cit_registration_date' => ['nullable', 'date'],
            'default_vat_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $t = Tenant::findOrFail($id);
        $t->fill($data);
        $t->save();

        return $this->respondSuccess(['message' => 'Tax settings updated.', 'data' => $t]);
    }
}

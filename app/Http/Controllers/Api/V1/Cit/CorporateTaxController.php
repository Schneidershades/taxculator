<?php

namespace App\Http\Controllers\Api\V1\Cit;

use App\Http\Controllers\Controller;
use App\Models\CorporateTaxTransaction;
use App\Services\Tax\CorporateTaxService;
use App\Http\Requests\Tax\CorporateTaxCreateFormRequest;

class CorporateTaxController extends Controller
{
    public function preview(CorporateTaxCreateFormRequest $request)
    {
        $data = app(CorporateTaxService::class)->preview($request->validated());
        return $this->respondSuccess([
            'message' => 'Corporate tax preview computed successfully.',
            'data'    => $data,
        ]);
    }

    public function store(CorporateTaxCreateFormRequest $request)
    {
        $payload = $request->validated();
        $key = $request->header('Idempotency-Key');

        // Short-circuit if the key already exists → 200
        if ($key) {
            $idem = substr($key, 0, 80);
            $existing = CorporateTaxTransaction::where('idempotency_key', $idem)->first();

            if ($existing) {
                return $this->respondSuccess([
                    'message' => 'Corporate tax transaction retrieved successfully (idempotent replay).',
                    'data'    => $existing->statement ?? [],
                    'id'      => $existing->id,
                ], 200);
            }

            // pass the key to the service for creation
            $payload['idempotency'] = $idem;
        }

        // No existing tx → create new → 201
        $tx = app(CorporateTaxService::class)->register($payload);

        return $this->respondSuccess([
            'message' => 'Corporate tax transaction created successfully.',
            'data'    => $tx->statement ?? [],
            'id'      => $tx->id,
        ], 201);
    }
}

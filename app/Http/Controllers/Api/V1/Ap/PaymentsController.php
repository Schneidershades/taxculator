<?php

namespace App\Http\Controllers\Api\V1\Ap;

use App\Http\Controllers\Controller;
use App\Services\ArAp\ArApService;
use App\Support\Tenancy;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'vendor_id' => ['nullable','integer'],
            'bank_account_id' => ['required','integer'],
            'amount' => ['required','numeric','min:0.01'],
            'currency_code' => ['nullable','string','size:3'],
            'allocations' => ['nullable','array'],
            'allocations.*.bill_id' => ['required','integer'],
            'allocations.*.amount' => ['required','numeric','min:0.01'],
        ]);

        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);

        $res = app(ArApService::class)->recordPayment($tenant->id, $data);
        return $this->respondSuccess(['message' => 'Payment recorded.', 'data' => ['id' => $res->id, 'allocations' => $res->allocations]], 201);
    }
}


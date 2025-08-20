<?php

namespace App\Http\Controllers\Api\V1\Tax;

use App\Http\Controllers\Controller;
use App\Services\Tax\TaxTransactionService;
use App\Http\Requests\Tax\TaxTransactionCreateFormRequest;

class TaxCalculationController extends Controller
{
    public function store(TaxTransactionCreateFormRequest $request, TaxTransactionService $service)
    {
        $data = $service->preview($request->validated());

        return $this->respondSuccess([
            'message' => 'Tax preview calculated successfully.',
            'data'    => $data,
        ], 200);
    }
}

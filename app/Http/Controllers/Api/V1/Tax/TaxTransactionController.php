<?php

namespace App\Http\Controllers\Api\V1\Tax;

use App\Models\TaxTransaction;
use App\Http\Controllers\Controller;
use App\Services\Tax\TaxTransactionService;
use App\Http\Requests\Tax\TaxTransactionCreateFormRequest;

class TaxTransactionController extends Controller
{
    public function store(TaxTransactionCreateFormRequest $request)
    {
        $tx = app(TaxTransactionService::class)->register($request->validated());
        return $this->showOne($tx->load('relations'));
    }

    public function show(int $id)
    {
        $tx = TaxTransaction::with('relations')->findOrFail($id);
        return $this->showOne($tx);
    }
}

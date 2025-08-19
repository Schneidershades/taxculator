<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\TaxTransactionCreateFormRequest;
use App\Services\Tax\TaxTransactionService;

class TaxTransactionController extends Controller
{
    public function store(TaxTransactionCreateFormRequest $request)
    {
        $tx = app(TaxTransactionService::class)->register($request->validated());
        return $this->showOne($tx->load('relations'));
    }
}

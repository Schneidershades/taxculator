<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\TaxTransactionCreateFormRequest;
use App\Services\Tax\TaxTransactionService;

class TaxTransactionController extends Controller
{
    private $service;

    public function __construct(TaxTransactionService $service)
    {
        $this->service = $service;
    }

    public function store(TaxTransactionCreateFormRequest $request)
    {
        return $this->showOne($this->service->register($request));
    }
}

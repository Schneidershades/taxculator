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
    /**
    * @OA\Post(
    *      path="/api/v1/tax-transactions",
    *      operationId="taxTransactions",
    *      tags={"company"},
    *      summary="Post annual tax of an employee",
    *      description="Post annual tax of an employee",
    *      @OA\RequestBody(
    *          required=true,
    *          @OA\JsonContent(ref="#/components/schemas/TaxTransactionCreateFormRequest")
    *      ),
    *      @OA\Response(
    *          response=200,
    *          description="Successful signin",
    *          @OA\MediaType(
    *             mediaType="application/json",
    *         ),
    *       ),
    *      @OA\Response(
    *          response=400,
    *          description="Bad Request"
    *      ),
    *      @OA\Response(
    *          response=401,
    *          description="unauthenticated",
    *      ),
    *      @OA\Response(
    *          response=403,
    *          description="Forbidden"
    *      ),
    *      security={ {"bearerAuth": {}} },
    * )
    */
    public function store (TaxTransactionCreateFormRequest $request)
    {
    	return $this->showOne($this->service->register($request));
    }
}

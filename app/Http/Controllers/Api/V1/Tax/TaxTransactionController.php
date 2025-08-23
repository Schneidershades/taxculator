<?php

namespace App\Http\Controllers\Api\V1\Tax;

use App\Models\TaxTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Services\Tax\TaxTransactionService;
use App\Http\Requests\Tax\TaxTransactionCreateFormRequest;

class TaxTransactionController extends Controller
{
    public function store(TaxTransactionCreateFormRequest $request)
    {
        $key = $request->header('Idempotency-Key');

        if ($key) {
            $existing = TaxTransaction::query()
                ->where('idempotency_key', substr((string)$key, 0, 80))
                ->first();

            if ($existing) {
                return $this->showOne($existing->load('relations')); // 200 retrieved
            }
        }

        $payload = $request->validated();
        if ($key) {
            $payload['idempotency_key'] = substr((string)$key, 0, 80);
        }

        $tx = app(TaxTransactionService::class)->register($payload);

        return $this->showOne($tx->load('relations'), 201); // 201 created
    }

    public function show(int $id)
    {
        $tx = TaxTransaction::with('relations')->findOrFail($id);
        return $this->showOne($tx);
    }

    public function statement(int $id)
    {
        $tx = TaxTransaction::with('relations')->findOrFail($id);

        return $this->respondSuccess([
            'message' => 'Statement retrieved successfully.',
            'data'    => $tx->statement ?? [],
        ]);
    }

    public function statementPdf(int $id)
    {
        $tx = TaxTransaction::with('relations')->findOrFail($id);
        $statement = $tx->statement ?? [];

        $pdf = Pdf::loadView('tax.statement', ['s' => $statement]);

        // download with a friendly name
        return $pdf->download("tax-statement-{$tx->id}.pdf");
    }
}

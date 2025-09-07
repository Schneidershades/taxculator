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

    public function packLinks(int $id)
    {
        $tx = TaxTransaction::findOrFail($id);
        $ttl = now()->addMinutes(5);
        $pdf = url()->temporarySignedRoute('pit.pack.pdf', $ttl, ['id' => $tx->id]);
        $csv = url()->temporarySignedRoute('pit.pack.csv', $ttl, ['id' => $tx->id]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => compact('pdf','csv')]);
    }

    public function downloadPackPdf(int $id)
    {
        $tx = TaxTransaction::findOrFail($id);
        $s = $tx->statement ?? [];
        $pdf = Pdf::loadView('tax.pit_pack', ['tx' => $tx, 's' => $s]);
        return $pdf->download("pit_pack_{$tx->id}.pdf");
    }

    public function downloadPackCsv(int $id)
    {
        $tx = TaxTransaction::findOrFail($id);
        $s = $tx->statement ?? [];
        $rows = [
            ['Item','Value'],
            ['Gross income', (string)($s['amounts']['gross_income'] ?? 0)],
            ['Taxable income', (string)($s['amounts']['taxable_income'] ?? 0)],
            ['Country tax', (string)($s['amounts']['country_tax'] ?? 0)],
            ['State tax', (string)($s['amounts']['state_tax'] ?? 0)],
            ['Local tax', (string)($s['amounts']['local_tax'] ?? 0)],
            ['Total tax', (string)($s['amounts']['total_tax'] ?? 0)],
            ['Net tax due', (string)($s['amounts']['net_tax_due'] ?? 0)],
        ];
        $csv = $this->csv($rows);
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pit_pack_'.$tx->id.'.csv"',
        ]);
    }

    private function csv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        foreach ($rows as $row) { fputcsv($out, $row); }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return (string) $csv;
    }
}

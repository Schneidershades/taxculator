<?php

namespace App\Http\Controllers\Api\V1\Cit;

use App\Http\Controllers\Controller;
use App\Models\CorporateTaxTransaction;
use App\Services\Tax\CorporateTaxService;
use App\Http\Requests\Tax\CorporateTaxCreateFormRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class CorporateTaxController extends Controller
{
    public function index()
    {
        $q = CorporateTaxTransaction::query()->orderByDesc('id');
        if (request()->filled('from')) $q->where('created_at', '>=', request('from'));
        if (request()->filled('to')) $q->where('created_at', '<=', request('to'));
        return $this->showAll($q->paginate(20));
    }
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

    // ----- Filing Pack (PDF + CSV) -----

    public function packLinks(int $id)
    {
        $tx = CorporateTaxTransaction::findOrFail($id);
        $ttl = now()->addMinutes(5);
        $pdf = url()->temporarySignedRoute('cit.pack.pdf', $ttl, ['id' => $tx->id]);
        $csv = url()->temporarySignedRoute('cit.pack.csv', $ttl, ['id' => $tx->id]);
        return $this->respondSuccess(['message' => 'Export links generated.', 'data' => compact('pdf','csv')]);
    }

    public function downloadPackPdf(int $id)
    {
        $tx = CorporateTaxTransaction::findOrFail($id);
        $statement = $tx->statement ?? [];
        $pdf = Pdf::loadView('tax.cit_pack', ['tx' => $tx, 's' => $statement]);
        return $pdf->download("cit_pack_{$tx->id}.pdf");
    }

    public function downloadPackCsv(int $id)
    {
        $tx = CorporateTaxTransaction::findOrFail($id);
        $s = $tx->statement ?? [];
        $rows = [
            ['Item','Value'],
            ['Profit before adjustment', (string)($s['amounts']['profit_before_adjustment'] ?? 0)],
            ['Adjustments', (string)($s['amounts']['adjustments'] ?? 0)],
            ['Loss relief applied', (string)($s['amounts']['loss_relief_applied'] ?? 0)],
            ['Taxable profit', (string)($s['amounts']['taxable_profit'] ?? 0)],
            ['Calculated tax', (string)($s['amounts']['calculated_tax'] ?? 0)],
            ['Minimum tax', (string)($s['amounts']['minimum_tax'] ?? 0)],
            ['Tax payable', (string)($s['amounts']['tax_payable'] ?? 0)],
        ];
        $csv = $this->csv($rows);
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cit_pack_'.$tx->id.'.csv"',
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

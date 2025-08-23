<?php

namespace App\Http\Controllers\Api\V1\Vat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\VatInvoiceCreateFormRequest;
use App\Http\Requests\Tax\VatReturnPreviewRequest;
use App\Services\Tax\VatService;

class VatController extends Controller
{
    public function createInvoice(VatInvoiceCreateFormRequest $request)
    {
        try {
            $payload = $request->validated();
            if ($key = $request->header('Idempotency-Key')) {
                $payload['idempotency'] = substr($key, 0, 80);
            }

            $inv = app(VatService::class)->createInvoice($payload);

            return $this->respondSuccess([
                'message' => 'VAT invoice recorded successfully.',
                'data'    => [
                    'id'          => $inv->id,
                    'direction'   => $inv->direction,
                    'period'      => $inv->period,
                    'net_total'   => (float)$inv->net_total,
                    'vat_total'   => (float)$inv->vat_total,
                    'gross_total' => (float)$inv->gross_total,
                    'lines'       => $inv->lines->map(fn($l) => [
                        'cat'  => $l->category_code,
                        'net'  => (float)$l->net_amount,
                        'rate' => (float)$l->vat_rate,
                        'vat'  => (float)$l->vat_amount,
                        'rc'   => (bool)$l->reverse_charge,
                    ])->all(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            // In test env this will bubble to the response so you can see it
            report($e);
            return $this->respondError($e->getMessage(), 500);
        }
    }

    public function previewReturn(VatReturnPreviewRequest $request)
    {
        $data = app(VatService::class)->previewReturn($request->validated());
        return $this->respondSuccess([
            'message' => 'VAT return preview computed successfully.',
            'data'    => $data,
        ]);
    }

    public function fileReturn(VatReturnPreviewRequest $request)
    {
        $ret = app(VatService::class)->fileReturn($request->validated());
        return $this->respondSuccess([
            'message' => 'VAT return filed successfully.',
            'data'    => [
                'id'         => $ret->id,
                'period'     => $ret->period,
                'output_vat' => (float)$ret->output_vat,
                'input_vat'  => (float)$ret->input_vat,
                'net_vat'    => (float)$ret->net_vat,
                'status'     => $ret->status,
            ],
        ], 201);
    }
}

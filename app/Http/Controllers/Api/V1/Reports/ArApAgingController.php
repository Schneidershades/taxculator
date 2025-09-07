<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Models\ApPaymentAllocation;
use App\Models\ArReceiptAllocation;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\DB; 

class ArApAgingController extends Controller
{
    public function ar(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $asOf = $request->query('as_of') ?: now()->toDateString();

        // Per invoice outstanding = invoice.total - sum(receipt allocations)
        $invoices = \App\Models\Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->select('id','customer_id','date','total')
            ->get();
        $allocSums = ArReceiptAllocation::query()
            ->join('ar_receipts as r', 'r.id', '=', 'ar_receipt_allocations.ar_receipt_id')
            ->where('r.tenant_id', $tenant->id)
            ->groupBy('invoice_id')
            ->select('invoice_id', DB::raw('SUM(amount) as paid'))
            ->pluck('paid','invoice_id');

        $buckets = ['0-30'=>0.0,'31-60'=>0.0,'61-90'=>0.0,'90+'=>0.0];
        $byCustomer = [];
        foreach ($invoices as $inv) {
            $age = \Carbon\Carbon::parse($inv->date)->diffInDays(\Carbon\Carbon::parse($asOf));
            $paid = (float)($allocSums[$inv->id] ?? 0);
            $out = max(0, (float)$inv->total - $paid);
            if ($out <= 0) continue;
            $bucket = $age <= 30 ? '0-30' : ($age <= 60 ? '31-60' : ($age <= 90 ? '61-90' : '90+'));
            $buckets[$bucket] += $out;
            $byCustomer[$inv->customer_id][$bucket] = ($byCustomer[$inv->customer_id][$bucket] ?? 0) + $out;
        }

        return $this->respondSuccess(['message' => 'AR aging generated.', 'data' => ['as_of' => $asOf, 'totals' => $buckets, 'by_customer' => $byCustomer]]);
    }

    public function ap(Request $request)
    {
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $asOf = $request->query('as_of') ?: now()->toDateString();

        $bills = \App\Models\Bill::query()
            ->where('tenant_id', $tenant->id)
            ->select('id','vendor_id','date','total')
            ->get();
        $paySums = ApPaymentAllocation::query()
            ->join('ap_payments as p', 'p.id', '=', 'ap_payment_allocations.ap_payment_id')
            ->where('p.tenant_id', $tenant->id)
            ->groupBy('bill_id')
            ->select('bill_id', DB::raw('SUM(amount) as paid'))
            ->pluck('paid','bill_id');

        $buckets = ['0-30'=>0.0,'31-60'=>0.0,'61-90'=>0.0,'90+'=>0.0];
        $byVendor = [];
        foreach ($bills as $bill) {
            $age = \Carbon\Carbon::parse($bill->date)->diffInDays(\Carbon\Carbon::parse($asOf));
            $paid = (float)($paySums[$bill->id] ?? 0);
            $out = max(0, (float)$bill->total - $paid);
            if ($out <= 0) continue;
            $bucket = $age <= 30 ? '0-30' : ($age <= 60 ? '31-60' : ($age <= 90 ? '61-90' : '90+'));
            $buckets[$bucket] += $out;
            $byVendor[$bill->vendor_id][$bucket] = ($byVendor[$bill->vendor_id][$bucket] ?? 0) + $out;
        }

        return $this->respondSuccess(['message' => 'AP aging generated.', 'data' => ['as_of' => $asOf, 'totals' => $buckets, 'by_vendor' => $byVendor]]);
    }
}


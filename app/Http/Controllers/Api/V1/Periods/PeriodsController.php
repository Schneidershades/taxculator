<?php

namespace App\Http\Controllers\Api\V1\Periods;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Support\Tenancy;
use Illuminate\Http\Request;

class PeriodsController extends Controller
{
    public function index()
    {
        $tenant = Tenancy::current();
        $q = AccountingPeriod::query()->when($tenant, fn($qq) => $qq->where('tenant_id', $tenant->id))->orderByDesc('period');
        return $this->showAll($q->get());
    }

    public function close(Request $request)
    {
        $data = $request->validate(['period' => ['required','regex:/^\d{4}-\d{2}$/']]);
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $p = AccountingPeriod::updateOrCreate(['tenant_id' => $tenant->id, 'period' => $data['period']], [
            'status' => 'closed', 'closed_at' => now(),
        ]);
        return $this->respondSuccess(['message' => 'Period closed.', 'data' => $p]);
    }

    public function open(Request $request)
    {
        $data = $request->validate(['period' => ['required','regex:/^\d{4}-\d{2}$/']]);
        $tenant = Tenancy::current();
        if (!$tenant) return $this->respondError('Tenant not identified.', 422);
        $p = AccountingPeriod::updateOrCreate(['tenant_id' => $tenant->id, 'period' => $data['period']], [
            'status' => 'open', 'closed_at' => null,
        ]);
        return $this->respondSuccess(['message' => 'Period opened.', 'data' => $p]);
    }
}


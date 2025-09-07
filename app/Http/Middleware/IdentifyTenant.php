<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->header('X-Tenant')
            ?? ($request->route('tenant') ?? null);

        $tenant = null;
        if (is_string($slug) && $slug !== '') {
            $tenant = Tenant::where('slug', $slug)->first();
        }

        Tenancy::setCurrent($tenant);

        return $next($request);
    }
}


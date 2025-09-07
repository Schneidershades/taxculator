<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);
        if (strcasecmp((string)$user->role, $role) !== 0) {
            return response()->json(['message' => 'Forbidden. Requires role: '.$role], 403);
        }
        return $next($request);
    }
}


<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerifiedApi
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);
        if (!$user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail) return $next($request);
        if ($user->hasVerifiedEmail()) return $next($request);
        return response()->json(['message' => 'Email not verified.'], 403);
    }
}


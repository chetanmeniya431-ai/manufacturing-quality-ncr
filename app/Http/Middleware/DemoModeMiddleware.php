<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DemoModeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        if (! auth()->check()) {
            return $next($request);
        }

        if (auth()->user()->hasRole('Super Admin')) {
            return $next($request);
        }

        if ($request->routeIs('logout')) {
            return $next($request);
        }

        return response()->json(['message' => 'Demo mode: write actions are disabled.'], 403);
    }
}

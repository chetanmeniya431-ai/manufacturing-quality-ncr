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

        // Livewire requests: let through — client-side fetch override is the gate.
        // Never return 403 to Livewire; it renders the JSON body as component HTML.
        if ($request->header('X-Livewire')) {
            return $next($request);
        }

        // Regular browser form POST: redirect back with a flash message
        return back()->with('demo_warning', 'This is a live demo. Write actions are disabled.');
    }
}

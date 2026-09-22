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

        // For Livewire requests: never return 403 (Livewire renders the body as component HTML).
        // The client-side fetch override (in the layout) intercepts writes before they reach here.
        if ($request->header('X-Livewire')) {
            // Always allow the DemoContactModal (users can submit the contact form)
            if (str_contains($request->getContent(), 'demo-contact-modal')) {
                return $next($request);
            }
            // Return a valid no-op: no component updates, page stays intact
            return response()->json(['components' => [], 'assets' => []], 200);
        }

        // Regular browser form POST: redirect back with a flash message
        return back()->with('demo_warning', 'This is a live demo. Write actions are disabled.');
    }
}

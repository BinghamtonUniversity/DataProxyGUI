<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            // Important: use 'away' to force a full-page redirect
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->away(route('oidc.redirect'));
        }

        return $next($request);
    }

}

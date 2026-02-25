<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts access to super-admin-only routes (e.g. settings/servers).
 * Returns an accessible, user-friendly Access Denied page for Inertia requests.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->super_admin) {
            if ($request->inertia()) {
                return Inertia::render('AccessDenied', [
                    'server_slug' => $request->route('server_slug'),
                    'title' => 'Access denied',
                    'message' => 'Unauthorized',
                ])->toResponse($request)->setStatusCode(403);
            }

            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}

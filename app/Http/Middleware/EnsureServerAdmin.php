<?php

namespace App\Http\Middleware;

use App\Services\ServerUserPolicyService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts access to Environments and Activity Logs to server admins only.
 * Uses the server's "users" resource admin flag for the current proxy server.
 * Returns an accessible, user-friendly Access Denied page for Inertia requests.
 */
class EnsureServerAdmin
{
    public function __construct(
        protected ServerUserPolicyService $serverUserPolicy
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $serverSlug = $request->route('server_slug');
        if (!$serverSlug) {
            return $next($request);
        }

        if (!$this->serverUserPolicy->isAdminForServer($serverSlug)) {
            if ($request->inertia()) {
                return Inertia::render('AccessDenied', [
                    'server_slug' => $serverSlug,
                ])->toResponse($request)->setStatusCode(403);
            }
            
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}

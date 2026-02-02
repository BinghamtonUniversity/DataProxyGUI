<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ProxyServerConfig;
use Inertia\Inertia;


class SetProxyServer
{
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->route('server_slug');

        if (!$slug) {
            $firstServer = ProxyServerConfig::where('is_active', true)
                ->orderBy('id')
                ->first();
            
            if (!$firstServer) {
                return response()->json([
                    'error' => 'No proxy servers available'
                ], 404);
            }
            
            $slug = $firstServer->slug;
        }
        
        // Verify the server exists
        $server = ProxyServerConfig::where('slug', $slug)
            ->where('is_active', true)
            ->first();
        
        if (!$server) {
            return response()->json([
                'error' => 'Invalid proxy server'
            ], 404);
        }

        // Store server config in request for easy access
        $request->attributes->set('proxy_server', $server);

        Inertia::share('server_slug', $slug);

        // Store in session
        // session(['current_proxy_server' => $slug]);
        
        return $next($request);
    }
}
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
        if ($request->is('settings/no-servers-available')) {
            Inertia::share('server_slug', null);
            return $next($request);
        }
        
        $slug = $request->route('server_slug');

        if (!$slug) {
            $firstServer = ProxyServerConfig::where('is_active', true)
                ->orderBy('id')
                ->first();
            
            if (!$firstServer) {
                // Redirect 
                return redirect()->route('no-servers-available');;
            }   
            
            $slug = $firstServer->slug;
        }
        
        // Verify the server exists
        $server = ProxyServerConfig::where('slug', $slug)
            ->where('is_active', true)
            ->first();
        
        if (!$server) {
            // Redirect to /settings/no-servers-available
            return redirect()->route('no-servers-available');
        }
        

        // Store server config in request for easy access
        $request->attributes->set('proxy_server', $server);

        Inertia::share('server_slug', $slug);
        
        return $next($request);
    }
}
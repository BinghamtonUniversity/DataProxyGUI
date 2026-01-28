<?php

namespace App\Http\Controllers;

use App\Models\ProxyServerConfig;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Request;

class ProxyServerController extends Controller
{
    // Inertia page
    public function index(Request $request): Response
    {
        $servers = ProxyServerConfig::where('is_active', true)
            ->select('id', 'name', 'slug', 'server')
            ->get();

        $currentServerSlug = $request->attributes->get('proxy_server')?->slug;
        
        return Inertia::render('ProxyServers/Index', [
            'servers' => $servers,
            'current' => $currentServerSlug
        ]);
    }
    
    // JSON API endpoint
    public function getServers(Request $request): JsonResponse
    {
        $servers = ProxyServerConfig::where('is_active', true)
            ->select('id', 'name', 'slug', 'server')
            ->get();

        $currentServerSlug = $request->attributes->get('proxy_server')?->slug;
        
        return response()->json([
            'servers' => $servers,
            'current' => $currentServerSlug
        ]);
    }
}
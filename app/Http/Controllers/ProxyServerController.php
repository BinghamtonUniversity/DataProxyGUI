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
        // should we fetch all servers or only active ones?
        $servers = ProxyServerConfig::where('is_active', true)
            ->select('id', 'name', 'slug', 'server', 'username', 'password', 'is_active')
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
            ->select('id', 'name', 'slug', 'server', 'username', 'password', 'is_active', 'type')
            ->get();

        $currentServerSlug = $request->attributes->get('proxy_server')?->slug;
        
        
        return response()->json([
            'servers' => $servers,
            'current' => $currentServerSlug
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        $server = ProxyServerConfig::create($request->all());
        return response()->json($server);
    }
    public function update(Request $request, $id): JsonResponse
    {
        $server = ProxyServerConfig::find($id);
        $server->update($request->all());
        return response()->json($server);
    }
    public function destroy($id): JsonResponse
    {
        $server = ProxyServerConfig::find($id);
        $server->delete();
        return response()->json($server);
    }
        // Bulk update servers - send an array of servers to update
    // each server should have an id and the fields to update or create
    public function bulkUpdate(Request $request): JsonResponse
    {
        $servers = $request->json()->all();
        $updatedServers = [];
      
        foreach ($servers as $serverData) {
            try {
                if (isset($serverData['id']) && $serverData['id']) {

                    
                    $serverModel = ProxyServerConfig::find($serverData['id']);
                    if ($serverModel) {
                       
                        $serverModel->update($serverData);
                        dd($serverModel);
                        $updatedServers[] = $serverModel;
                    }
                } else {
                    $serverModel = ProxyServerConfig::create($serverData);
                    $updatedServers[] = $serverModel;
                }
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
        return response()->json($updatedServers);
    }
}
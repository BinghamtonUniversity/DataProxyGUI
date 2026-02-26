<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ResourcesController extends BaseServerController{
    // ===========================================
    // Resources 
    // ===========================================
    public function resourcesByTypeIndex(string $server_slug, $type): JsonResponse
    {
        $endpoint = "resources/type/{$type}";

        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? `Unknown error occurred on {$server_slug} side.`;

        return response()->json([
            'error' => $errorMessage,
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesIndex(string $server_slug): JsonResponse
    {
        $endpoint = "resources";
        // Log::info('Fetching all resources', ['endpoint' => $endpoint]);
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);
        // Log::info('Request result', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? "Unknown error occurred on {$server_slug} side.";

        return response()->json([
            'error' => $errorMessage,
            'status' => $result['status']
        ], $result['status']);
    }
    
    public function resourcesStore(Request $request, string $server_slug): JsonResponse
    {
        // Log::info('Store method called', [
        //     'request_data' => $request->all()
        // ]);
        $result = $this->makeBackendRequest('POST', 'resources', $request->all(), [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }
       
        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? "Unknown error occurred on {$server_slug} side.";

        return response()->json([
            'error' => $errorMessage,
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesUpdate(Request $request, string $server_slug, string $resource_id): JsonResponse
    {
        // Log::info('resourcesUpdate called', ['resource_id' => $resource_id]);

        $endpoint = "resources/{$resource_id}";

        $requestData = $request->all();
        
        $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $server_slug);
        // Log::info('Request result', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        // Extract a meaningful error message from the response
        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? "Unknown error occurred on {$server_slug} side.";

        return response()->json([
            'error' => $errorMessage,
            'api_id' => $resource_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesDestroy(string $server_slug, string $id): JsonResponse
    {
        $endpoint = "resources/{$id}";
        
        $result = $this->makeBackendRequest('DELETE', $endpoint, [], [], $server_slug);
        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('resource') . ' deleted successfully'
            ]);
        }
        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? "Unknown error occurred on {$server_slug} side.";

        return response()->json([
            'error' => $errorMessage,
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }
}
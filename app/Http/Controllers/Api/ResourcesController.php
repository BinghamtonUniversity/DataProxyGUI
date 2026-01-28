<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ResourcesController extends BaseDjangoController{
    // ===========================================
    // Resources 
    // ===========================================
    public function resourcesByTypeIndex($server_slug, $api_type, $type): JsonResponse
    {
        $endpoint = "resources/type/{$type}";

        // $result = $this->makeDjangoRequest('GET', $endpoint);
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? `Unknown error occurred on {$api_type} side.`;

        return response()->json([
            'error' => $errorMessage,
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesIndex(): JsonResponse
    {
        $endpoint = "resources";
        // Log::info('Fetching all resources', ['endpoint' => $endpoint]);
        $result = $this->makeDjangoRequest('GET', $endpoint);
        // Log::info('Django request result', [
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
            ?? 'Unknown error occurred on Django side.';

        return response()->json([
            'error' => $errorMessage,
            'status' => $result['status']
        ], $result['status']);
    }
    
    public function resourcesStore(Request $request): JsonResponse
    {
        // Log::info('Store method called', [
        //     'request_data' => $request->all()
        // ]);
        $result = $this->makeDjangoRequest('POST', 'resources', $request->all());

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }
       
        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? 'Unknown error occurred on Django side.';

        return response()->json([
            'error' => $errorMessage,
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesUpdate(Request $request, string $resource_id): JsonResponse
    {
        // Log::info('resourcesUpdate called', ['resource_id' => $resource_id]);

        $endpoint = "resources/{$resource_id}";

        $requestData = $request->all();
        
        $result = $this->makeDjangoRequest('PUT', $endpoint, $requestData);
        // Log::info('Django request result', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        // Extract a meaningful error message from the Django response
        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? 'Unknown error occurred on Django side.';

        return response()->json([
            'error' => $errorMessage,
            'api_id' => $resource_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesDestroy($id): JsonResponse
    {
        $endpoint = "resources/{$id}";
        
        $result = $this->makeDjangoRequest('DELETE', $endpoint);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('resource') . ' deleted successfully'
            ]);
        }
        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? 'Unknown error occurred on Django side.';

        return response()->json([
            'error' => $errorMessage,
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }
}
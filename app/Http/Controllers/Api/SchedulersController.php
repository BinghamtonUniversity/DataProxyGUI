<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SchedulersController extends BaseDjangoController{
    
    // ===========================================
    // Schedulers
    // ===========================================
    public function schedulersIndex(string $server_slug): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', 'schedulers', [], [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch schedulers}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersStore(Request $request, $server_slug): JsonResponse
    {
        $result = $this->makeBackendRequest('POST', 'schedulers', $request->all(), [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }

        return response()->json([
            'error' => "Failed to create schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersUpdate(Request $request, string $server_slug, string $id): JsonResponse
    {
        $result = $this->makeBackendRequest('PUT', "schedulers/{$id}", $request->all(), [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to update schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersDestroy(string $server_slug, string $id): JsonResponse
    {
        $result = $this->makeBackendRequest('DELETE', "schedulers/{$id}", [], [], $server_slug);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('schedulers') . ' deleted successfully'
            ]);
        }

        return response()->json([
            'error' => "Failed to delete schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }
}
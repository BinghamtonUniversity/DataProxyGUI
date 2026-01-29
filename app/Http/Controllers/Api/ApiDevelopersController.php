<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiDevelopersController extends BaseDjangoController{
    /**
     * Get API developers for a specific API
     */
    public function getApiDevelopers(string $server_slug, string $api_id): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', "apis/{$api_id}/developers", [], [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to fetch API developers for API {$api_id}",
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Create a new API developer assignment
     */
    public function createApiDeveloper(Request $request, string $server_slug, string $api_id): JsonResponse
    {
        $result = $this->makeBackendRequest('POST', "apis/{$api_id}/developers", $request->all(), [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }

        if ($result['status'] === 403) {    
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to assign developer to API {$api_id}",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Update an API developer assignment
     */
    public function updateApiDeveloper(Request $request, string $server_slug, string $api_id, $id): JsonResponse
    {
        $result = $this->makeBackendRequest('PUT', "apis/{$api_id}/developers/{$id}", $request->all(), [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {    
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to update API developer assignment",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Delete an API developer assignment
     */
    public function deleteApiDeveloper(string $server_slug, string $api_id, $id): JsonResponse
    {
        $result = $this->makeBackendRequest('DELETE', "apis/{$api_id}/developers/{$id}", [], [], $server_slug);

        if ($result['success']) {
            return response()->json([
                'message' => 'API developer assignment removed successfully'
            ]);
        }

        if ($result['status'] === 403) {    
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to remove API developer assignment",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

}
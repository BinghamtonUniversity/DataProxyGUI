<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiDevelopersController extends BaseDjangoController{
    /**
     * Get API developers for a specific API
     */
    public function getApiDevelopers($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "apis/{$id}/developers");

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to fetch API developers for API {$id}",
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Create a new API developer assignment
     */
    public function createApiDeveloper(Request $request, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('POST', "apis/{$id}/developers", $request->all());

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }

        if ($result['status'] === 403) {    
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to assign developer to API {$id}",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Update an API developer assignment
     */
    public function updateApiDeveloper(Request $request, $api_id, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('PUT', "apis/{$api_id}/developers/{$id}", $request->all());

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
    public function deleteApiDeveloper($api_id, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('DELETE', "apis/{$api_id}/developers/{$id}");

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
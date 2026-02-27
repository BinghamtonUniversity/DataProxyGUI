<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiInstancesController extends BaseServerController{

    public function apiInstancesIndex(Request $request, string $server_slug): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', 'api_instances', [], [], $server_slug);

        if ( $result['success']) { // $phpResult['success'] ||
            return response()->json($result['data']);
        }

        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? "Unknown error occurred on {$server_slug} side.";

        return response()->json([
            'error' => $errorMessage,
            'status' => $result['status'],
        ], 500);
    }

    public function apiInstancesStore(Request $request, string $server_slug): JsonResponse
    {
        // Log::info('ApiInstancesStore called');

        $requestData = $request->all();
        
        $result = $this->makeBackendRequest('POST', 'api_instances', $requestData, [], $server_slug);
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
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

   public function apiInstancesUpdate(Request $request, string $server_slug, string $api_instance_id): JsonResponse
    {
        // Log::info('ApiInstancesUpdate called', ['api_instance_id' => $api_instance_id, 'api_type' => $api_type]);

        $endpoint = "api_instances/{$api_instance_id}";
        $requestData = $request->all();

        try {
            $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $server_slug);

            // Log::info('Backend request result', [
            //     'success' => $result['success'],
            //     'status' => $result['status'],
            //     'data' => $result['data']
            // ]);

            if ($result['success']) {
                return response()->json($result['data']);
            }

            // Check for various possible error keys
            $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? "Unknown error occurred on {$server_slug} side.";

            return response()->json([
                'error' => 'Failed to update API Instance.',
                'details' => $errorMessage,
                'status' => $result['status'],
                'api_id' => $api_instance_id,
            ], $result['status']);

        } catch (\Throwable $e) {
            Log::error('Exception during API instance update', [
                'api_instance_id' => $api_instance_id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Internal server error while updating API Instance.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function apiInstancesDestroy(string $server_slug, string $id): JsonResponse
    {
        $endpoint = "api_instances/{$id}";
        
        $result = $this->makeBackendRequest('DELETE', $endpoint, [], [], $server_slug);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('api_instance') . ' deleted successfully'
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

    // ===========================================
    // API Instance by ID - AJAX call for fetching single instance
    // ===========================================
    public function ApiInstancesEditIndex(string $server_slug, string $instance_id): JsonResponse
    {
        // Log::info('ApiInstancesEditIndex called', ['instance_id' => $instance_id, 'api_type' => $api_type]);

        $endpoint = "api_instances/{$instance_id}";
        
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);
        // Log::info('APIInstanceEditIndex Backend request result', [
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
            'api_id' => $instance_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function ApiInstancesEditUpdate(Request $request, string $server_slug, string $instance_id, ): JsonResponse
    {
        // Log::info('ApiInstancesEditUpdate called', ['instance_id' => $instance_id, 'api_type' => $api_type]);

        $endpoint = "api_instances/{$instance_id}";
        $requestData = $request->all();

        try {
            $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $server_slug);

            // Log::info('Backend request result', [
            //     'success' => $result['success'],
            //     'status' => $result['status'],
            //     'data' => $result['data']
            // ]);

            if ($result['success']) {
                return response()->json($result['data']);
            }

            // Try to extract a meaningful error message
            $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? "Unknown error occurred on {$server_slug} side.";

            return response()->json([
                'error' => 'Failed to update API Instance.',
                'details' => $errorMessage,
                'status' => $result['status'],
                'api_id' => $instance_id,
            ], $result['status']);

        } catch (\Throwable $e) {
            Log::error('Exception during ApiInstancesEditUpdate', [
                'instance_id' => $instance_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error while updating API Instance.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

}
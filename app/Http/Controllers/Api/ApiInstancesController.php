<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiInstancesController extends BaseDjangoController{

    public function apiInstancesIndex(): JsonResponse
    {
        // $phpResult = $this->makeBackendRequest('GET', 'api_instances', [], [], 'php'); //TO:DO - remove this when php talks to same database as django
        $djangoResult = $this->makeBackendRequest('GET', 'api_instances', [], [], 'django');

        if ( $djangoResult['success']) { // $phpResult['success'] ||
            $merged = [];
            $merged = array_merge(
                is_array($djangoResult['data'] ?? []) ? $djangoResult['data'] : [],
                // is_array($phpResult['data'] ?? []) ? $phpResult['data'] : []
            );
            // Add api_type to each Django result
            // if (is_array($djangoResult['data'] ?? [])) {
            //     foreach ($djangoResult['data'] as $item) {
            //         if (is_array($item)) {
            //             $item['api_type'] = 'python';
            //             $merged[] = $item;
            //         }
            //     }
            // }

            // Add api_type to each PHP result
            // if (is_array($phpResult['data'] ?? [])) {
            //     foreach ($phpResult['data'] as $item) {
            //         if (is_array($item)) {
            //             $item['api_type'] = 'php';
            //             $merged[] = $item;
            //         }
            //     }
            // }

            return response()->json($merged);
        }

        $phpErrorMessage = $phpResult['data']['error']
            ?? $phpResult['data']['detail']
            ?? $phpResult['data']['message']
            ?? 'Unknown error occurred on PHP side.';

        $djangoErrorMessage = $djangoResult['data']['error']
            ?? $djangoResult['data']['detail']
            ?? $djangoResult['data']['message']
            ?? 'Unknown error occurred on Django side.';

        return response()->json([
            'error' => $phpErrorMessage . ' | ' . $djangoErrorMessage,
            'django_status' => $djangoResult['status'],
            // 'php_status' => $phpResult['status'],
        ], 500);
    }

    public function apiInstancesStore(Request $request, string $api_type): JsonResponse
    {
        Log::info('ApiInstancesStore called');

        $requestData = $request->all();
        
        $result = $this->makeBackendRequest('POST', 'api_instances', $requestData, [], $api_type);
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
            ?? `Unknown error occurred on {$api_type} side.`;

        return response()->json([
            'error' => $errorMessage,
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

   public function apiInstancesUpdate(Request $request, string $api_type, string $api_instance_id): JsonResponse
    {
        Log::info('ApiInstancesUpdate called', ['api_instance_id' => $api_instance_id, 'api_type' => $api_type]);

        $endpoint = "api_instances/{$api_instance_id}";
        $requestData = $request->all();

        try {
            $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $api_type);

            Log::info('Backend request result', [
                'success' => $result['success'],
                'status' => $result['status'],
                'data' => $result['data']
            ]);

            if ($result['success']) {
                return response()->json($result['data']);
            }

            // Check for various possible error keys
            $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? `Unknown error occurred on {$api_type} side.`;

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


    public function apiInstancesDestroy(string $api_type, string $id): JsonResponse
    {
        $endpoint = "api_instances/{$id}";
        
        $result = $this->makeBackendRequest('DELETE', $endpoint, [], [], $api_type);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('api_instance') . ' deleted successfully'
            ]);
        }

        $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? `Unknown error occurred on {$api_type} side.`;

        return response()->json([
            'error' => $errorMessage,
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    // ===========================================
    // API Instance by ID - AJAX call for fetching single instance
    // ===========================================
    public function ApiInstancesEditIndex(string $api_type, string $instance_id): JsonResponse
    {
        Log::info('ApiInstancesEditIndex called', ['instance_id' => $instance_id, 'api_type' => $api_type]);

        $endpoint = "api_instances/{$instance_id}";
        
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $api_type);
        Log::info('APIInstanceEditIndex Backend request result', [
            'success' => $result['success'],
            'status' => $result['status'],
            'data' => $result['data']
        ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? `Unknown error occurred on {$api_type} side.`;

        return response()->json([
            'error' => $errorMessage,
            'api_id' => $instance_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function ApiInstancesEditUpdate(Request $request, string $api_type, string $instance_id, ): JsonResponse
    {
        Log::info('ApiInstancesEditUpdate called', ['instance_id' => $instance_id, 'api_type' => $api_type]);

        $endpoint = "api_instances/{$instance_id}";
        $requestData = $request->all();

        try {
            $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $api_type);

            Log::info('Backend request result', [
                'success' => $result['success'],
                'status' => $result['status'],
                'data' => $result['data']
            ]);

            if ($result['success']) {
                return response()->json($result['data']);
            }

            // Try to extract a meaningful error message
            $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? `Unknown error occurred on {$api_type} side.`;

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
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiUsersController extends BaseDjangoController{
    // ===========================================
    // API Users
    // ===========================================
    public function apiUsersIndex(): JsonResponse
    {
        $phpResult = $this->makeBackendRequest('GET', 'api_users', [], [], 'php');
        $djangoResult = $this->makeBackendRequest('GET', 'api_users', [], [], 'django');

        if ($phpResult['success'] || $djangoResult['success']) {
            $merged = array_merge(
                is_array($djangoResult['data'] ?? []) ? $djangoResult['data'] : [],
                is_array($phpResult['data'] ?? []) ? $phpResult['data'] : []
            );

            return response()->json($merged);
        }

        return response()->json([
            'error' => 'Failed to fetch API Users from both backends',
            'django_status' => $djangoResult['status'],
            'php_status' => $phpResult['status'],
        ], 500);
    }

    public function apiUsersStore(Request $request): JsonResponse
    {
        Log::info('apiUsersStore called');

        $requestData = $request->all();
        
        $result = $this->makeDjangoRequest('POST', "api_users", $requestData);
        // Log::info('Django request result', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? 'Unknown error occurred on Django side.';

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $errorMessage,
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apiUsersUpdate(Request $request, string $api_user_id): JsonResponse
    {
        Log::info('apiUsersUpdate called', ['api_instance_id' => $api_user_id]);

        $endpoint = "api_users/{$api_user_id}";

        $requestData = $request->all();
        
        $result = $this->makeDjangoRequest('PUT', $endpoint, $requestData);
        Log::info('Django request result', [
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
            ?? 'Unknown error occurred on Django side.';

        return response()->json([
            'error' => $errorMessage,
            'api_id' => $api_user_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function apiUsersDestroy($id): JsonResponse
    {
        $endpoint = "api_users/{$id}";
        
        $result = $this->makeDjangoRequest('DELETE', $endpoint);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('api_user') . ' deleted successfully'
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

    public function apiUsersDecryptedSecret($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "api_users/{$id}/decrypted_secret");
        return response()->json($result['data']);

        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? 'Unknown error occurred on Django side.';

        return response()->json([
            'error' => $errorMessage,
            'status' => $result['status']
        ], $result['status']);
    }
}
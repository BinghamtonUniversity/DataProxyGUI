<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiUsersController extends BaseDjangoController{
    // ===========================================
    // API Users
    // ===========================================
    public function apiUsersIndex(Request $request, string $server_slug): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', 'api_users', [], [], $server_slug);


        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => 'Failed to fetch API Users from the backend',
            'django_status' => $result['status'],
        ], 500);
    }

    public function apiUsersStore(Request $request, string $server_slug): JsonResponse
    {
        // Log::info('apiUsersStore called');

        $requestData = $request->all();
        
        $result = $this->makeBackendRequest('POST', "api_users", $requestData, [], $server_slug);
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

    public function apiUsersUpdate(Request $request, string $server_slug,string $api_user_id): JsonResponse
    {
        // Log::info('apiUsersUpdate called', ['api_instance_id' => $api_user_id]);

        $endpoint = "api_users/{$api_user_id}";

        $requestData = $request->all();
        
        $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $server_slug);
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
            'api_id' => $api_user_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function apiUsersDestroy(string $server_slug, string $id): JsonResponse
    {
        $endpoint = "api_users/{$id}";
        
        $result = $this->makeBackendRequest('DELETE', $endpoint, [], [], $server_slug);
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

    public function apiUsersDecryptedSecret(string $server_slug, string $id): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', "api_users/{$id}/decrypted_secret", [], [], $server_slug);
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
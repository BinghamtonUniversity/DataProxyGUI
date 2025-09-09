<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    private $djangoBaseUrl;
    private $uniqueId;
    private $apiUser;
    private $apiPassword;

    public function __construct()
    {
        $this->djangoBaseUrl = config('services.django.base_url');
        $this->uniqueId = 'B00694089';
        $this->apiUser = config('services.django.api_user');
        $this->apiPassword = config('services.django.api_password');
    }

    /**
     * Generic method to make HTTP requests to Django API
     */
    private function makeDjangoRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $defaultHeaders = [
            'X-Unique-Id' => $this->uniqueId,
            'Accept' => 'application/json',
        ];

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $defaultHeaders['Content-Type'] = 'application/json';
        }

        $headers = array_merge($defaultHeaders, $headers);

        try {
            $response = Http::withBasicAuth($this->apiUser, $this->apiPassword)
                           ->withHeaders($headers);

            switch (strtoupper($method)) {
                case 'GET':
                    $response = $response->get("{$this->djangoBaseUrl}/api/{$endpoint}");
                    break;
                case 'POST':
                    $response = $response->post("{$this->djangoBaseUrl}/api/{$endpoint}", $data);
                    break;
                case 'PUT':
                    $response = $response->put("{$this->djangoBaseUrl}/api/{$endpoint}", $data);
                    break;
                case 'DELETE':
                    $response = $response->delete("{$this->djangoBaseUrl}/api/{$endpoint}");
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
            }

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'response' => $response
            ];

        } catch (\Exception $e) {
            Log::error("Django API request failed: {$e->getMessage()}", [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data
            ]);

            return [
                'success' => false,
                'status' => 500,
                'data' => ['error' => 'Internal server error'],
                'response' => null
            ];
        }
    }

    /**
     * Generic index method for any resource
     */
    public function index(string $resource): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', $resource);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch {$resource}",
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Generic store method for any resource
     */
    public function store(Request $request, string $resource): JsonResponse
    {
        $result = $this->makeDjangoRequest('POST', $resource, $request->all());

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }

        return response()->json([
            'error' => "Failed to create {$resource}",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Generic update method for any resource
     */
    public function update(Request $request, string $resource, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('PUT', "{$resource}/{$id}", $request->all());

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to update {$resource}",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Generic destroy method for any resource
     */
    public function destroy(string $resource, $id): JsonResponse
    {
        // Handle special case for environments DELETE endpoint
        $endpoint = $resource === 'environments' ? "{$resource}?id={$id}" : "{$resource}/{$id}";
        
        $result = $this->makeDjangoRequest('DELETE', $endpoint);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst($resource) . ' deleted successfully'
            ]);
        }

        return response()->json([
            'error' => "Failed to delete {$resource}",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    // Specific methods for apis (if you need custom logic)
    public function apisIndex(): JsonResponse
    {
        return $this->index('apis');
    }

    public function apisStore(Request $request): JsonResponse
    {
        return $this->store($request, 'apis');
    }

    public function apisUpdate(Request $request, $id): JsonResponse
    {
        return $this->update($request, 'apis', $id);
    }

    public function apisDestroy($id): JsonResponse
    {
        return $this->destroy('apis', $id);
    }




    // Specific methods for users (if you need custom logic)
    public function usersIndex(): JsonResponse
    {
        return $this->index('users');
    }

    public function usersStore(Request $request): JsonResponse
    {
        return $this->store($request, 'users');
    }

    public function usersUpdate(Request $request, $id): JsonResponse
    {
        return $this->update($request, 'users', $id);
    }

    public function usersDestroy($id): JsonResponse
    {
        return $this->destroy('users', $id);
    }
}
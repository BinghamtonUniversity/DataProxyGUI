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
    /**
     * Generic resource controller - handles all CRUD operations dynamically
     * 
     * @param string $resource The resource name (e.g., 'environments', 'users', 'apis')
     * @param string $action The action to perform (index, store, update, destroy)
     * @param Request|null $request The request object (for store/update operations)
     * @param mixed $id The resource ID (for update/destroy operations)
     * @return JsonResponse
     */
    public function handleResource(string $resource, string $action, Request $request = null, $id = null): JsonResponse
    {
        // Validate resource name
        $allowedResources = ['environments', 'users', 'apis'];
        if (!in_array($resource, $allowedResources)) {
            return response()->json([
                'error' => "Resource '{$resource}' not supported",
                'allowed_resources' => $allowedResources
            ], 400);
        }

        // Validate action
        $allowedActions = ['index', 'store', 'update', 'destroy'];
        if (!in_array($action, $allowedActions)) {
            return response()->json([
                'error' => "Action '{$action}' not supported",
                'allowed_actions' => $allowedActions
            ], 400);
        }

        // Route to appropriate method
        switch ($action) {
            case 'index':
                return $this->index($resource);
            case 'store':
                if (!$request) {
                    return response()->json(['error' => 'Request object required for store action'], 400);
                }
                return $this->store($request, $resource);
            case 'update':
                if (!$request || !$id) {
                    return response()->json(['error' => 'Request object and ID required for update action'], 400);
                }
                return $this->update($request, $resource, $id);
            case 'destroy':
                if (!$id) {
                    return response()->json(['error' => 'ID required for destroy action'], 400);
                }
                return $this->destroy($resource, $id);
            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }
    }

    /**
     * Get the latest version of a specific API
     */
    public function getLatestApiVersion($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "apis/{$id}/versions/latest");

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch latest version for API {$id}",
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Update API code/configuration
     */
    public function updateApiCode(Request $request, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('PUT', "apis/{$id}/code", $request->all());

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to update API code for API {$id}",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Get API developers for a specific API
     */
    public function getApiDevelopers($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "apis/{$id}/developers");

        if ($result['success']) {
            return response()->json($result['data']);
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

        return response()->json([
            'error' => "Failed to remove API developer assignment",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    /**
     * Magic method to handle dynamic resource calls
     * This allows calling methods like: environmentsIndex(), usersStore(), etc.
     * 
     * @param string $method
     * @param array $parameters
     * @return JsonResponse
     */
    public function __call(string $method, array $parameters): JsonResponse
    {
        // Parse method name to extract resource and action
        // Pattern: {resource}{Action} (e.g., environmentsIndex, usersStore)
        if (preg_match('/^([a-z]+)(Index|Store|Update|Destroy)$/', $method, $matches)) {
            $resource = $matches[1];
            $action = strtolower($matches[2]);
            
            // Get current request instance for all actions that need it
            $request = request();
            
            switch ($action) {
                case 'index':
                    // No parameters needed
                    return $this->handleResource($resource, $action, null, null);
                    
                case 'store':
                    // No ID needed, just request
                    return $this->handleResource($resource, $action, $request, null);
                    
                case 'update':
                    // ID is the first parameter, request is current request
                    $id = $parameters[0] ?? null;
                    return $this->handleResource($resource, $action, $request, $id);
                    
                case 'destroy':
                    // ID is the first parameter, no request needed
                    $id = $parameters[0] ?? null;
                    return $this->handleResource($resource, $action, null, $id);
                    
                default:
                    throw new \BadMethodCallException("Unknown action: {$action}");
            }
        }

        // If method doesn't match pattern, throw error
        throw new \BadMethodCallException("Method {$method} not found");
    }
}
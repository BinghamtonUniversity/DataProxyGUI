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
        $this->uniqueId = config('services.django.unique_id');
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
       
        $fullUrl = "{$this->djangoBaseUrl}/api/{$endpoint}";
        Log::info('Making Django request', [
            'method' => $method,
            'full_url' => $fullUrl,
            'endpoint' => $endpoint,
            'headers' => $headers,
            'data' => $data,
            'django_base_url' => $this->djangoBaseUrl
        ]);

        try {
            $response = Http::withBasicAuth($this->apiUser, $this->apiPassword)
                        ->withHeaders($headers);

            switch (strtoupper($method)) {
                case 'GET':
                    $response = $response->get($fullUrl);
                    break;
                case 'POST':
                    $response = $response->post($fullUrl, $data);
                    break;
                case 'PUT':
                    $response = $response->put($fullUrl, $data);
                    break;
                case 'DELETE':
                    $response = $response->delete($fullUrl);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
            }

            // Add response debugging
            // Log::info('Django response received', [
            //     'status' => $response->status(),
            //     'successful' => $response->successful(),
            //     // 'body' => $response->body(),
            //     'headers' => $response->headers()
            // ]);

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
                'full_url' => $fullUrl,
                'data' => $data,
                'exception' => $e->getTraceAsString()
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
        Log::info('Store method called', [
            'resource' => $resource,
            'request_data' => $request->all()
        ]);
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

    // ===========================================
    // APIS
    // ===========================================
    public function apisIndex(): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', 'apis');

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch apis}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisShow($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "apis/{$id}");

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch api {$id}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisStore(Request $request): JsonResponse
    {
        Log::info('Store method called', [
            'request_data' => $request->all()
        ]);
        $result = $this->makeDjangoRequest('POST', 'apis', $request->all());

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }
       
        return response()->json([
            'error' => "Failed to create apis",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisUpdate(Request $request, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('PUT', "apis/{$id}", $request->all());

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to update apis",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisDestroy($id): JsonResponse
    {
        // Handle special case for environments DELETE endpoint
        $endpoint = "apis/{$id}";
        
        $result = $this->makeDjangoRequest('DELETE', $endpoint);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('apis') . ' deleted successfully'
            ]);
        }

        return response()->json([
            'error' => "Failed to delete apis",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    // ===========================================
    // API Instances
    // ===========================================
    public function apiInstancesIndex(): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', 'api_instances');

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch api instances}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function apiInstancesStore(Request $request): JsonResponse
    {
        Log::info('ApiInstancesStore called');

        $requestData = $request->all();
        
        $result = $this->makeDjangoRequest('POST', "api_instances", $requestData);
        // Log::info('Django request result', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to POST API Instance details",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apiInstancesUpdate(Request $request, string $api_instance_id): JsonResponse
    {
        Log::info('ApiInstancesStore called', ['api_instance_id' => $api_instance_id]);

        $endpoint = "api_instances/{$api_instance_id}";

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

        return response()->json([
            'error' => "Failed to PUT API Instance details",
            'api_id' => $api_instance_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function apiInstancesDestroy($id): JsonResponse
    {
        $endpoint = "api_instances/{$id}";
        
        $result = $this->makeDjangoRequest('DELETE', $endpoint);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('api_instance') . ' deleted successfully'
            ]);
        }

        return response()->json([
            'error' => "Failed to delete api instances",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    // ===========================================
    // API Instance by ID - AJAX call for fetching single instance
    // ===========================================
    public function ApiInstancesEditIndex(Request $request, string $instance_id): JsonResponse
    {
        Log::info('ApiInstancesEditIndex called', ['instance_id' => $instance_id]);

        $endpoint = "api_instances/{$instance_id}";
        
        $result = $this->makeDjangoRequest('GET', $endpoint);
        // Log::info('Django request result', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch API details",
            'api_id' => $instance_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function ApiInstancesEditUpdate(Request $request, string $instance_id): JsonResponse
    {
        Log::info('ApiInstancesEditUpdate called', ['instance_id' => $instance_id]);

        $endpoint = "api_instances/{$instance_id}";
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

        return response()->json([
            'error' => "Failed to put API Instance details",
            'api_id' => $instance_id,
            'status' => $result['status']
        ], $result['status']);
    }

    // ===========================================
    // Schedulers
    // ===========================================
    public function schedulersIndex(): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', 'schedulers');

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch schedulers}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersStore(Request $request): JsonResponse
    {
        $result = $this->makeDjangoRequest('POST', 'schedulers', $request->all());

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }

        return response()->json([
            'error' => "Failed to create schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersUpdate(Request $request, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('PUT', "schedulers/{$id}", $request->all());

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to update schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersDestroy($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('DELETE', "schedulers/{$id}");

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

    // ===========================================
    // API Users
    // ===========================================
    public function apiUsersIndex(): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', 'api_users');

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch api users}",
            'status' => $result['status']
        ], $result['status']);
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

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to POST API User",
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

        return response()->json([
            'error' => "Failed to PUT API User",
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

        return response()->json([
            'error' => "Failed to delete api user",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    // ===========================================
    // API Versions
    // ===========================================
    public function apiVersionsIndex($instance_id): JsonResponse
    {
        $endpoint = "apis/{$instance_id}/versions";

        $result = $this->makeDjangoRequest('GET', $endpoint);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch api versions for api {$instance_id}",
            'status' => $result['status']
        ], $result['status']);
    }

    // ===========================================
    // API Latest Version
    // ===========================================

    /**
     * APIEdit Index - Fetch API details with optional tab filtering
     */
    public function ApiEditIndex(Request $request, string $api_id): JsonResponse
    {
        Log::info('ApiEditIndex called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/versions/latest";
        
        $result = $this->makeDjangoRequest('GET', $endpoint);
        Log::info('Django request result', [
            'success' => $result['success'],
            'status' => $result['status'],
            'data' => $result['data']
        ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch API details",
            'api_id' => $api_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function ApiEditUpdate(Request $request, string $api_id): JsonResponse
    {
        Log::info('ApiEditStore called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/code";

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

        return response()->json([
            'error' => "Failed to PUT API details",
            'api_id' => $api_id,
            'status' => $result['status']
        ], $result['status']);
    }


    // ===========================================
    // Resources -- TO:DO - Move to separate controller and make djangorequest util function
    // ===========================================
    public function resourcesByTypeIndex($type): JsonResponse
    {
        $endpoint = "resources/type/{$type}";

        $result = $this->makeDjangoRequest('GET', $endpoint);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch resources of type {$type}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesIndex(): JsonResponse
    {
        $endpoint = "resources";
        Log::info('Fetching all resources', ['endpoint' => $endpoint]);
        $result = $this->makeDjangoRequest('GET', $endpoint);
        Log::info('Django request result', [
            'success' => $result['success'],
            'status' => $result['status'],
            'data' => $result['data']
        ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch resources",
            'status' => $result['status']
        ], $result['status']);
    }
    
    public function resourcesStore(Request $request): JsonResponse
    {
        Log::info('Store method called', [
            'request_data' => $request->all()
        ]);
        $result = $this->makeDjangoRequest('POST', 'resources', $request->all());

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }
       
        return response()->json([
            'error' => "Failed to create resource",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function resourcesUpdate(Request $request, string $resource_id): JsonResponse
    {
        Log::info('resourcesUpdate called', ['resource_id' => $resource_id]);

        $endpoint = "resources/{$resource_id}";

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

        return response()->json([
            'error' => "Failed to PUT API User",
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

        return response()->json([
            'error' => "Failed to delete resource",
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
    public function handleResource(string $resource, string $action, ?Request $request=null, $id = null): JsonResponse
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

    /**
     * Get all versions of a specific API
     */
    public function getApiVersions($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "apis/{$id}/versions");

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch versions for API {$id}",
        ], 500);
    }

    /**
     * Publish a new version of a specific API
     */
    public function publishApiVersion(Request $request, $id): JsonResponse
    {
        $data = $request->all();
        
        $result = $this->makeDjangoRequest('PUT', "apis/{$id}/publish", $data);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to publish version for API {$id}",
        ], 500);
    }

    /**
     * Get details of a specific API version
     */
    public function getApiVersionDetails( $api_id, $version_id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "apis/{$api_id}/versions/{$version_id}");

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch version details for API version {$version_id}",
        ], 500);
    }
}
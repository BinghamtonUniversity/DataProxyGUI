<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Faker\Provider\Base;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiController extends BaseDjangoController
{
    
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
        $endpoint = "{$resource}/{$id}";

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

        $phpResult = $this->makeBackendRequest('GET', 'apis', [], [], 'php'); //TO:DO - remove this when php talks to same database as django
        $djangoResult = $this->makeBackendRequest('GET', 'apis', [], [], 'django');

        if ($djangoResult['success'] || $phpResult['success']) { 
          
            $merged = array_merge(
                is_array($djangoResult['data'] ?? []) ? $djangoResult['data'] : [],
                is_array($phpResult['data'] ?? []) ? $phpResult['data'] : []
            );

            return response()->json($merged);
        }

        return response()->json([
            'error' => 'Failed to fetch APIs from both backends',
            'django_status' => $djangoResult['status'],
            'php_status' => $phpResult['status'],
        ], 500);
    }

    public function apisShow($api_type, $id): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', "apis/{$id}", [], [], $api_type);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch api {$id}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisStore(Request $request, string $api_type): JsonResponse
    {
        Log::info('Store method called', [
            'request_data' => $request->all()
        ]);
     
        $result = $this->makeBackendRequest('POST', 'apis', $request->all(), [], $api_type);

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }
       
        return response()->json([
            'error' => "Failed to create apis",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisUpdate(Request $request, string $api_type, string $id): JsonResponse
    {
        $result = $this->makeBackendRequest('PUT', "apis/{$id}", $request->all(), [], $api_type);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to update apis",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisDestroy(string $api_type, string $id): JsonResponse
    {
        // Handle special case for environments DELETE endpoint
        $endpoint = "apis/{$id}";
        
        $result = $this->makeBackendRequest('DELETE', $endpoint, [], [], $api_type);

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
    public function ApiEditIndex(Request $request, string $api_type, string $api_id): JsonResponse
    {
        Log::info('ApiEditIndex called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/versions/latest";

        // $backend = $request->query('backend');

        //$result = $this->makeBackendRequest($backend, 'GET', $endpoint);
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $api_type);
        Log::info('Backend request result', [
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

    public function ApiEditUpdate(Request $request, string $api_type, string $api_id): JsonResponse
    {
        Log::info('ApiEditUpdate called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/code";
        $requestData = $request->all();

        try {
            $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $api_type);

            Log::info('Request result', [
                'success' => $result['success'],
                'status' => $result['status'],
                'data' => $result['data']
            ]);

            if ($result['success']) {
                return response()->json($result['data']);
            }

            // Extract a meaningful error message from the Django response
            $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? 'Unknown error occurred on Django side.';

            return response()->json([
                'error' => 'Failed to update API details.',
                'details' => $errorMessage,
                'status' => $result['status'],
                'api_id' => $api_id,
            ], $result['status']);

        } catch (\Throwable $e) {
            Log::error('Exception during ApiEditUpdate', [
                'api_id' => $api_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error while updating API details.',
                'details' => $e->getMessage(),
            ], 500);
        }
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
    // ===========================================
    // API's Instances
    // ===========================================
    public function apisInstancesIndex($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', "apis/{$id}/instances");
    
        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch API's instances for API {$id}",
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
    public function getApiVersions($api_type, $id): JsonResponse
    {
        // $result = $this->makeDjangoRequest('GET', "apis/{$id}/versions");
        $result = $this->makeBackendRequest('GET', "apis/{$id}/versions", [], [], 'php');

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
    public function publishApiVersion(Request $request, $api_type, $id): JsonResponse
    {
        $data = $request->all();
        
        // $result = $this->makeDjangoRequest('PUT', "apis/{$id}/publish", $data);
        $result = $this->makeBackendRequest('PUT', "apis/{$id}/publish", $data, [], $api_type);

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
    public function getApiVersionDetails( $api_type, $api_id, $version_id): JsonResponse
    {
        // $result = $this->makeDjangoRequest('GET', "apis/{$api_id}/versions/{$version_id}");
        if ($api_type === 'php') {
            $result = $this->makeBackendRequest('GET', "api_versions/{$version_id}", [], [], $api_type);
        } else {
            $result = $this->makeDjangoRequest('GET', "apis/{$api_id}/versions/{$version_id}");
        }
        // $result = $this->makeBackendRequest('GET', "apis/{$api_id}/versions/{$version_id}", [], [], $api_type);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch version details for API version {$version_id}",
        ], 500);
    }
    public function apiVersionsList(): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', 'api_versions');
        return response()->json($result['data']);
        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch API versions",
            'status' => $result['status']
        ], $result['status']);
        
    }

    /**
     * Export API Version - Display JSON in new tab
     */
    public function exportApiVersion(Request $request, string $api_type, string $api_id)
    {
        Log::info('Export API Version called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/versions/latest";
        
        // $result = $this->makeDjangoRequest('GET', $endpoint);
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $api_type);
        // Log::info('Django request result for export', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        if ($result['success']) {
            // Return JSON with proper headers for display in browser
            return response()->json($result['data'], 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'inline; filename="api_' . $api_id . '_latest_version.json"'
            ]);
        }

        return response()->json([
            'error' => "Failed to fetch API version for export",
            'api_id' => $api_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function allApiVersionsIndex(): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', 'api_versions');

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch all API versions",
            'status' => $result['status']
        ], $result['status']);
    }
}
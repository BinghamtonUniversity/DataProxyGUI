<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Faker\Provider\Base;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiController extends BaseServerController
{
    
    /**
     * Generic index method for any resource
     */
    public function index(string $resource, string $server_slug): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', $resource, [], [], $server_slug);

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
    public function store(Request $request, string $resource, string $server_slug): JsonResponse
    {
        // Log::info('Store method called', [
        //     'resource' => $resource,
        //     'request_data' => $request->all()
        // ]);
        $result = $this->makeBackendRequest('POST', $resource, $request->all(), [], $server_slug);

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
    public function update(Request $request, string $resource, string $id, string $server_slug): JsonResponse
    {
        $result = $this->makeBackendRequest('PUT', "{$resource}/{$id}", $request->all(), [], $server_slug);

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
    public function destroy(string $resource, $id, string $server_slug): JsonResponse
    {
        // Handle special case for environments DELETE endpoint
        $endpoint = "{$resource}/{$id}";
        
        $result = $this->makeBackendRequest('DELETE', $endpoint, [], [], $server_slug);

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
    public function apisIndex(Request $request, string $server_slug): JsonResponse
    {

        $result = $this->makeBackendRequest('GET', 'apis', [], [], $server_slug);

        if ($result['success']) { 
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => 'Failed to fetch APIs',
            'status' => $result['status'],
        ], 500);
    }

    public function apisShow(string $server_slug, $id): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', "apis/{$id}", [], [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to fetch api {$id}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisStore(Request $request, string $server_slug): JsonResponse
    {
        // Log::info('Store method called', [
        //     'request_data' => $request->all()
        // ]);
     
        $result = $this->makeBackendRequest('POST', 'apis', $request->all(), [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }
       
        return response()->json([
            'error' => "Failed to create apis",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisUpdate(Request $request, string $server_slug, string $id): JsonResponse
    {
        $result = $this->makeBackendRequest('PUT', "apis/{$id}", $request->all(), [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }
        

        return response()->json([
            'error' => "Failed to update apis",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function apisDestroy(string $server_slug, string $id): JsonResponse
    {
        // Handle special case for environments DELETE endpoint
        $endpoint = "apis/{$id}";
        
        $result = $this->makeBackendRequest('DELETE', $endpoint, [], [], $server_slug);

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('apis') . ' deleted successfully'
            ]);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
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
    public function apiVersionsIndex(string $server_slug, $instance_id): JsonResponse
    {
        $endpoint = "apis/{$instance_id}/versions";

        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);

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
    public function ApiEditIndex(Request $request, string $server_slug,string $api_id): JsonResponse
    {
        // Log::info('ApiEditIndex called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/versions/latest";

        // $backend = $request->query('backend');

        //$result = $this->makeBackendRequest($backend, 'GET', $endpoint);
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);
        // Log::info('Backend request result', [
        //     'success' => $result['success'],
        //     'status' => $result['status'],
        //     'data' => $result['data']
        // ]);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to fetch API details",
            'api_id' => $api_id,
            'status' => $result['status']
        ], $result['status']);
    }

    public function ApiEditUpdate(Request $request, string $server_slug, string $api_id): JsonResponse
    {
        // Log::info('ApiEditUpdate called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/code";
        $requestData = $request->all();

        try {
            $result = $this->makeBackendRequest('PUT', $endpoint, $requestData, [], $server_slug);

            Log::info('Request result', [
                'success' => $result['success'],
                'status' => $result['status'],
                'data' => $result['data']
            ]);

            if ($result['success']) {
                return response()->json($result['data']);
            }

            // Extract a meaningful error message from the response
            $errorMessage = $result['data']['error']
                ?? $result['data']['detail']
                ?? $result['data']['message']
                ?? "Unknown error occurred on {$server_slug} side.";

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
    public function handleResource(string $resource, string $action, ?Request $request=null, $id = null, ?string $server_slug = null): JsonResponse
    {
        // Validate resource name
        $allowedResources = ['environments', 'users'];
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
                return $this->index($resource, $server_slug);
            case 'store':
                if (!$request) {
                    return response()->json(['error' => 'Request object required for store action'], 400);
                }
                return $this->store($request, $resource, $server_slug);
            case 'update':
                if (!$request || !$id) {
                    return response()->json(['error' => 'Request object and ID required for update action'], 400);
                }
                return $this->update($request, $resource, $id, $server_slug);
            case 'destroy':
                if (!$id) {
                    return response()->json(['error' => 'ID required for destroy action'], 400);
                }
                return $this->destroy($resource, $id, $server_slug);
            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }
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
            $server_slug = $request->route('server_slug');
            
            switch ($action) {
                case 'index':
                    // No parameters needed
                    return $this->handleResource($resource, $action, null, null, $server_slug);
                    
                case 'store':
                    // No ID needed, just request
                    return $this->handleResource($resource, $action, $request, null, $server_slug);
                    
                case 'update':
                    // ID is the first parameter, request is current request
                    $id = $parameters[1] ?? null;
                    return $this->handleResource($resource, $action, $request, $id, $server_slug);
                    
                case 'destroy':
                    // ID is the first parameter, no request needed
                    $id = $parameters[1] ?? null;
                    return $this->handleResource($resource, $action, null, $id, $server_slug);
                    
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
    public function getApiVersions(string $server_slug, $id): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', "apis/{$id}/versions", [], [], $server_slug);

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
    public function publishApiVersion(Request $request, string $server_slug, $id): JsonResponse
    {
        $data = $request->all();
        
        $result = $this->makeBackendRequest('PUT', "apis/{$id}/publish", $data, [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if ($result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to publish version for API {$id}",
        ], 500);
    }

    /**
     * Get details of a specific API version
     */
    public function getApiVersionDetails(string $server_slug, $version_id): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', "api_versions/{$version_id}", [], [], $server_slug);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        if( $result['status'] === 403) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'error' => "Failed to fetch version details for API version {$version_id}",
        ], 500);
    }
    public function apiVersionsList(Request $request, string $server_slug): JsonResponse
    {
        $result = $this->makeBackendRequest('GET', 'api_versions', [], [], $server_slug);
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
    public function exportApiVersion(Request $request, string $server_slug, string $api_id)
    {
        // Log::info('Export API Version called', ['api_id' => $api_id]);

        $endpoint = "apis/{$api_id}/versions/latest";
        
        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);
        // Log::info('Request result for export', [
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

}
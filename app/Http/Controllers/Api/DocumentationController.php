<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends BaseServerController{
   
    public function apiDocs($server_slug,$api_instance_id)
    {
        $endpoint = "api_docs/{$api_instance_id}";

        $result = $this->makeBackendRequest('GET', $endpoint, [], [], $server_slug);

        if ($result['success']) {
            // Return the HTML documentation directly
            $docs = $result['data']['docs'] ?? '';
            return response($docs)->header('Content-Type', 'text/html');
        }

        $errorMessage = $result['data']['error']
            ?? $result['data']['detail']
            ?? $result['data']['message']
            ?? "Unknown error occurred on {$server_slug} side.";

        // Return error page
        return response()->view('errors.api-error', [
            'error' => is_array($errorMessage) ? json_encode($errorMessage) : $errorMessage,
            'status' => $result['status']
        ], $result['status']);
    }
}
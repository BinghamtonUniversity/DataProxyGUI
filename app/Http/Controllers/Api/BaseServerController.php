<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ProxyServerConfig;

class BaseServerController extends Controller
{
    protected function getProxyConfig(string $slug): ?ProxyServerConfig
    {
        return ProxyServerConfig::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    protected function makeBackendRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        ?string $serverSlug = null
    ): array {
        // Get proxy config from slug
        if ($serverSlug) {
            $proxyConfig = $this->getProxyConfig($serverSlug);
            
            if (!$proxyConfig) {
                return [
                    'success' => false,
                    'status' => 404,
                    'data' => ['error' => 'Proxy server not found'],
                    'response' => null,
                ];
            }
            
            $baseUrl = $proxyConfig->server;
            $user = $proxyConfig->username;
            $password = $proxyConfig->getDecryptedPassword();
            
           

          
        } else {
            // Fallback to default config ?
            $baseUrl = config('services.django.base_url');
            $user = config('services.django.api_user');
            $password = config('services.django.api_password');
        }

        $defaultHeaders = [
            'X-Unique-Id' => Auth::user()->unique_id,
            'Accept' => 'application/json',
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $defaultHeaders['Content-Type'] = 'application/json';
        }

        $headers = array_merge($defaultHeaders, $headers);
        $fullUrl = "{$baseUrl}/api/{$endpoint}";

        try {
            $request = Http::withBasicAuth($user, $password)
                ->withHeaders($headers);

            $response = match (strtoupper($method)) {
                'GET' => $request->get($fullUrl),
                'POST' => $request->post($fullUrl, $data),
                'PUT' => $request->put($fullUrl, $data),
                'DELETE' => $request->delete($fullUrl),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            // log error
            Log::info("Proxy API request: {$method} {$fullUrl}", [
                'backend' => $baseUrl,
                'user' => $user,
                'request_data' => $data,
                'server_slug' => $serverSlug,
                'status' => $response->status(),
                'success' => $response->successful(),

            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'response' => $response,
            ];

        } catch (\Exception $e) {
            Log::error("Proxy API request failed: {$e->getMessage()}", [
                'server_slug' => $serverSlug,
                'method' => $method,
                'endpoint' => $endpoint,
                'url' => $fullUrl,
                'headers' => $headers,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'status' => 500,
                'data' => ['error' => 'Internal server error'],
                'response' => null,
            ];
        }
    }
}
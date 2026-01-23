<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaseDjangoController extends Controller
{
    protected $djangoBaseUrl;
    protected $apiUser;
    protected $apiPassword;

    protected $phpBaseUrl;
    protected $phpUser;
    protected $phpPassword;

    public function __construct()
    {
        $this->djangoBaseUrl = config('services.django.base_url');
        $this->apiUser = config('services.django.api_user');
        $this->apiPassword = config('services.django.api_password');

        $this->phpBaseUrl = config('services.php.base_url');
        $this->phpUser = config('services.php.api_user');
        $this->phpPassword = config('services.php.api_password');
    }

    // protected function makeDjangoRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    // {
    //     $defaultHeaders = [
    //         'X-Unique-Id' => Auth::user()->unique_id,
    //         'Accept' => 'application/json',
    //     ];

    //     if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    //         $defaultHeaders['Content-Type'] = 'application/json';
    //     }

    //     $headers = array_merge($defaultHeaders, $headers);
    //     $fullUrl = "{$this->djangoBaseUrl}/api/{$endpoint}";

    //     // Log::info('Making Django request', compact('method', 'fullUrl', 'data', 'headers'));

    //     try {
    //         $request = Http::withBasicAuth($this->apiUser, $this->apiPassword)
    //             ->withHeaders($headers);

    //         $response = match (strtoupper($method)) {
    //             'GET' => $request->get($fullUrl),
    //             'POST' => $request->post($fullUrl, $data),
    //             'PUT' => $request->put($fullUrl, $data),
    //             'DELETE' => $request->delete($fullUrl),
    //             default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
    //         };

    //         return [
    //             'success' => $response->successful(),
    //             'status' => $response->status(),
    //             'data' => $response->json(),
    //             'response' => $response,
    //         ];

    //     } catch (\Exception $e) {
    //         Log::error("Django API request failed: {$e->getMessage()}", [
    //             'method' => $method,
    //             'endpoint' => $endpoint,
    //             'headers' => $headers,
    //             'data' => $data,
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return [
    //             'success' => false,
    //             'status' => 500,
    //             'data' => ['error' => 'Internal server error'],
    //             'response' => null,
    //         ];
    //     }
    // }

    protected function makeBackendRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        string $backend = 'django', // or 'php'
    ): array {
        $config = match ($backend) {
            'php' => [
                'baseUrl' => $this->phpBaseUrl,
                'user' => $this->phpUser,
                'pass' => $this->phpPassword,
            ],
            default => [
                'baseUrl' => $this->djangoBaseUrl,
                'user' => $this->apiUser,
                'pass' => $this->apiPassword,
            ],
        };

        $defaultHeaders = [
            'X-Unique-Id' => Auth::user()->unique_id,
            'Accept' => 'application/json',
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $defaultHeaders['Content-Type'] = 'application/json';
        }

        $headers = array_merge($defaultHeaders, $headers);
        $fullUrl = "{$config['baseUrl']}/api/{$endpoint}";

        try {
            $request = Http::withBasicAuth($config['user'], $config['pass'])
                ->withHeaders($headers);

            $response = match (strtoupper($method)) {
                'GET' => $request->get($fullUrl),
                'POST' => $request->post($fullUrl, $data),
                'PUT' => $request->put($fullUrl, $data),
                'DELETE' => $request->delete($fullUrl),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'response' => $response,
            ];

        } catch (\Exception $e) {
            Log::error("{$backend} API request failed: {$e->getMessage()}", [
                'backend' => $backend,
                'method' => $method,
                'endpoint' => $endpoint,
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

    protected function makeDjangoRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeBackendRequest($method, $endpoint, $data, $headers, 'django');
    }
}
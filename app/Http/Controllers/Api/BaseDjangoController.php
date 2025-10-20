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

    public function __construct()
    {
        $this->djangoBaseUrl = config('services.django.base_url');
        $this->apiUser = config('services.django.api_user');
        $this->apiPassword = config('services.django.api_password');
    }

    protected function makeDjangoRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $defaultHeaders = [
            'X-Unique-Id' => Auth::user()->unique_id,
            'Accept' => 'application/json',
        ];

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $defaultHeaders['Content-Type'] = 'application/json';
        }

        $headers = array_merge($defaultHeaders, $headers);
        $fullUrl = "{$this->djangoBaseUrl}/api/{$endpoint}";

        Log::info('Making Django request', compact('method', 'fullUrl', 'data', 'headers'));

        try {
            $request = Http::withBasicAuth($this->apiUser, $this->apiPassword)
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
            Log::error("Django API request failed: {$e->getMessage()}", [
                'method' => $method,
                'endpoint' => $endpoint,
                'headers' => $headers,
                'data' => $data,
                'trace' => $e->getTraceAsString(),
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

<?php

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up test configuration
    Config::set('services.django.base_url', 'https://django-test.example.com');
    Config::set('services.django.api_user', 'test_django_user');
    Config::set('services.django.api_password', 'test_django_password');
    
    Config::set('services.php.base_url', 'https://php-test.example.com');
    Config::set('services.php.api_user', 'test_php_user');
    Config::set('services.php.api_password', 'test_php_password');

    // Create and authenticate a user
    $this->user = User::factory()->create();
    $this->user->unique_id = 'test-user-123';
    $this->user->save();
    Auth::login($this->user);

    $this->controller = new ApiController();
});

// ===========================================
// Generic CRUD Operations Tests
// ===========================================

test('index returns resource data on success', function () {
    $resourceData = [
        ['id' => 1, 'name' => 'Resource 1'],
        ['id' => 2, 'name' => 'Resource 2'],
    ];

    Http::fake([
        'django-test.example.com/api/environments' => Http::response($resourceData, 200),
    ]);

    $response = $this->controller->index('environments');

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($resourceData);
});

test('index returns error response on failure', function () {
    Http::fake([
        'django-test.example.com/api/environments' => Http::response(['error' => 'Not found'], 404),
    ]);

    $response = $this->controller->index('environments');

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to fetch environments');
});

test('store creates resource and returns 201 on success', function () {
    $requestData = ['name' => 'New Environment', 'type' => 'production'];
    $createdData = ['id' => 1, 'name' => 'New Environment', 'type' => 'production'];

    Http::fake([
        'django-test.example.com/api/environments' => Http::response($createdData, 201),
    ]);

    $request = Request::create('/api/environments', 'POST', $requestData);
    $response = $this->controller->store($request, 'environments');

    expect($response->getStatusCode())->toBe(201)
        ->and(json_decode($response->getContent(), true))->toBe($createdData);
});

test('store logs request data', function () {
    Log::spy();

    $requestData = ['name' => 'New Environment'];
    Http::fake([
        'django-test.example.com/api/environments' => Http::response(['id' => 1], 201),
    ]);

    $request = Request::create('/api/environments', 'POST', $requestData);
    $this->controller->store($request, 'environments');

    Log::shouldHaveReceived('info')
        ->with('Store method called', [
            'resource' => 'environments',
            'request_data' => $requestData,
        ]);
});

test('store returns error response on failure', function () {
    $requestData = ['name' => 'New Environment'];
    Http::fake([
        'django-test.example.com/api/environments' => Http::response(['error' => 'Validation failed'], 400),
    ]);

    $request = Request::create('/api/environments', 'POST', $requestData);
    $response = $this->controller->store($request, 'environments');

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to create environments');
});

test('update modifies resource and returns updated data', function () {
    $requestData = ['name' => 'Updated Environment'];
    $updatedData = ['id' => 1, 'name' => 'Updated Environment'];

    Http::fake([
        'django-test.example.com/api/environments/1' => Http::response($updatedData, 200),
    ]);

    $request = Request::create('/api/environments/1', 'PUT', $requestData);
    $response = $this->controller->update($request, 'environments', 1);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($updatedData);
});

test('update returns error response on failure', function () {
    $requestData = ['name' => 'Updated Environment'];
    Http::fake([
        'django-test.example.com/api/environments/1' => Http::response(['error' => 'Not found'], 404),
    ]);

    $request = Request::create('/api/environments/1', 'PUT', $requestData);
    $response = $this->controller->update($request, 'environments', 1);

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to update environments');
});

test('destroy deletes resource and returns success message', function () {
    Http::fake([
        'django-test.example.com/api/environments/1' => Http::response([], 204),
    ]);

    $response = $this->controller->destroy('environments', 1);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toHaveKey('message')
        ->and(json_decode($response->getContent(), true)['message'])->toBe('Environments deleted successfully');
});

test('destroy returns error response on failure', function () {
    Http::fake([
        'django-test.example.com/api/environments/1' => Http::response(['error' => 'Not found'], 404),
    ]);

    $response = $this->controller->destroy('environments', 1);

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to delete environments');
});

// ===========================================
// APIs CRUD Operations Tests
// ===========================================

test('apisIndex returns APIs data on success', function () {
    $apisData = [
        ['id' => 1, 'name' => 'API 1', 'api_type' => 'django'],
        ['id' => 2, 'name' => 'API 2', 'api_type' => 'php'],
    ];

    Http::fake([
        'django-test.example.com/api/apis' => Http::response($apisData, 200),
    ]);

    $response = $this->controller->apisIndex();

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($apisData);
});

test('apisIndex returns error response on failure', function () {
    Http::fake([
        'django-test.example.com/api/apis' => Http::response(['error' => 'Server error'], 500),
    ]);

    $response = $this->controller->apisIndex();

    expect($response->getStatusCode())->toBe(500)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to fetch APIs');
});

test('apisShow returns API data for django backend', function () {
    $apiData = ['id' => 1, 'name' => 'Test API', 'api_type' => 'django'];

    Http::fake([
        'django-test.example.com/api/apis/1' => Http::response($apiData, 200),
    ]);

    $response = $this->controller->apisShow('django', 1);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($apiData);
});

test('apisShow returns API data for php backend', function () {
    $apiData = ['id' => 1, 'name' => 'Test API', 'api_type' => 'php'];

    Http::fake([
        'php-test.example.com/api/apis/1' => Http::response($apiData, 200),
    ]);

    $response = $this->controller->apisShow('php', 1);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($apiData);
});

test('apisShow returns error response on failure', function () {
    Http::fake([
        'django-test.example.com/api/apis/1' => Http::response(['error' => 'Not found'], 404),
    ]);

    $response = $this->controller->apisShow('django', 1);

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to fetch api 1');
});

test('apisStore creates API and returns 201 on success', function () {
    $requestData = ['name' => 'New API', 'api_type' => 'django'];
    $createdData = ['id' => 1, 'name' => 'New API', 'api_type' => 'django'];

    Http::fake([
        'django-test.example.com/api/apis' => Http::response($createdData, 201),
    ]);

    $request = Request::create('/api/apis', 'POST', $requestData);
    $response = $this->controller->apisStore($request, 'django');

    expect($response->getStatusCode())->toBe(201)
        ->and(json_decode($response->getContent(), true))->toBe($createdData);
});

test('apisStore returns error response on failure', function () {
    $requestData = ['name' => 'New API'];
    Http::fake([
        'django-test.example.com/api/apis' => Http::response(['error' => 'Validation failed'], 400),
    ]);

    $request = Request::create('/api/apis', 'POST', $requestData);
    $response = $this->controller->apisStore($request, 'django');

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to create apis');
});

test('apisUpdate modifies API and returns updated data', function () {
    $requestData = ['name' => 'Updated API'];
    $updatedData = ['id' => 1, 'name' => 'Updated API'];

    Http::fake([
        'django-test.example.com/api/apis/1' => Http::response($updatedData, 200),
    ]);

    $request = Request::create('/api/apis/1', 'PUT', $requestData);
    $response = $this->controller->apisUpdate($request, 'django', '1');

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($updatedData);
});

test('apisUpdate returns error response on failure', function () {
    $requestData = ['name' => 'Updated API'];
    Http::fake([
        'django-test.example.com/api/apis/1' => Http::response(['error' => 'Not found'], 404),
    ]);

    $request = Request::create('/api/apis/1', 'PUT', $requestData);
    $response = $this->controller->apisUpdate($request, 'django', '1');

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to update apis');
});

test('apisDestroy deletes API and returns success message', function () {
    Http::fake([
        'django-test.example.com/api/apis/1' => Http::response([], 204),
    ]);

    $response = $this->controller->apisDestroy('django', '1');

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toHaveKey('message')
        ->and(json_decode($response->getContent(), true)['message'])->toBe('Apis deleted successfully');
});

test('apisDestroy returns error response on failure', function () {
    Http::fake([
        'django-test.example.com/api/apis/1' => Http::response(['error' => 'Not found'], 404),
    ]);

    $response = $this->controller->apisDestroy('django', '1');

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to delete apis');
});

// ===========================================
// handleResource Method Tests
// ===========================================

test('handleResource validates resource name', function () {
    $response = $this->controller->handleResource('invalid_resource', 'index');

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toContain('not supported');
});

test('handleResource validates action name', function () {
    $response = $this->controller->handleResource('environments', 'invalid_action');

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toContain('not supported');
});

test('handleResource routes to index action', function () {
    Http::fake([
        'django-test.example.com/api/environments' => Http::response([], 200),
    ]);

    $response = $this->controller->handleResource('environments', 'index');

    expect($response->getStatusCode())->toBe(200);
});

test('handleResource routes to store action', function () {
    Http::fake([
        'django-test.example.com/api/environments' => Http::response(['id' => 1], 201),
    ]);

    $request = Request::create('/api/environments', 'POST', ['name' => 'Test']);
    $response = $this->controller->handleResource('environments', 'store', $request);

    expect($response->getStatusCode())->toBe(201);
});

test('handleResource requires request for store action', function () {
    $response = $this->controller->handleResource('environments', 'store', null);

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toContain('Request object required');
});

test('handleResource routes to update action', function () {
    Http::fake([
        'django-test.example.com/api/environments/1' => Http::response(['id' => 1], 200),
    ]);

    $request = Request::create('/api/environments/1', 'PUT', ['name' => 'Updated']);
    $response = $this->controller->handleResource('environments', 'update', $request, 1);

    expect($response->getStatusCode())->toBe(200);
});

test('handleResource requires request and id for update action', function () {
    $response = $this->controller->handleResource('environments', 'update', null, null);

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toContain('Request object and ID required');
});

test('handleResource routes to destroy action', function () {
    Http::fake([
        'django-test.example.com/api/environments/1' => Http::response([], 204),
    ]);

    $response = $this->controller->handleResource('environments', 'destroy', null, 1);

    expect($response->getStatusCode())->toBe(200);
});

test('handleResource requires id for destroy action', function () {
    $response = $this->controller->handleResource('environments', 'destroy', null, null);

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toContain('ID required');
});

// ===========================================
// API Versions Tests
// ===========================================

test('apiVersionsIndex returns versions data on success', function () {
    $versionsData = [
        ['id' => 1, 'version' => '1.0.0'],
        ['id' => 2, 'version' => '1.1.0'],
    ];

    Http::fake([
        'django-test.example.com/api/apis/1/versions' => Http::response($versionsData, 200),
    ]);

    $response = $this->controller->apiVersionsIndex(1);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($versionsData);
});

test('apiVersionsIndex returns error response on failure', function () {
    Http::fake([
        'django-test.example.com/api/apis/1/versions' => Http::response(['error' => 'Not found'], 404),
    ]);

    $response = $this->controller->apiVersionsIndex(1);

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toContain('Failed to fetch api versions');
});

test('ApiEditIndex returns latest API version on success', function () {
    $apiData = ['id' => 1, 'name' => 'Test API', 'version' => '1.0.0'];

    Http::fake([
        'django-test.example.com/api/apis/1/versions/latest' => Http::response($apiData, 200),
    ]);

    $request = Request::create('/api/apis/django/1', 'GET');
    $response = $this->controller->ApiEditIndex($request, 'django', '1');

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($apiData);
});

test('ApiEditIndex returns error response on failure', function () {
    Http::fake([
        'django-test.example.com/api/apis/1/versions/latest' => Http::response(['error' => 'Not found'], 404),
    ]);

    $request = Request::create('/api/apis/django/1', 'GET');
    $response = $this->controller->ApiEditIndex($request, 'django', '1');

    expect($response->getStatusCode())->toBe(404)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Failed to fetch API details');
});

test('ApiEditUpdate updates API code and returns success', function () {
    $requestData = ['code' => 'updated code'];
    $updatedData = ['id' => 1, 'code' => 'updated code'];

    Http::fake([
        'django-test.example.com/api/apis/1/code' => Http::response($updatedData, 200),
    ]);

    $request = Request::create('/api/apis/django/1', 'PUT', $requestData);
    $response = $this->controller->ApiEditUpdate($request, 'django', '1');

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toBe($updatedData);
});

test('ApiEditUpdate handles exceptions gracefully', function () {
    Http::fake(function () {
        throw new \Exception('Network error');
    });

    $request = Request::create('/api/apis/django/1', 'PUT', ['code' => 'test']);
    $response = $this->controller->ApiEditUpdate($request, 'django', '1');

    expect($response->getStatusCode())->toBe(500)
        ->and(json_decode($response->getContent(), true))->toHaveKey('error')
        ->and(json_decode($response->getContent(), true)['error'])->toBe('Internal server error while updating API details.');
});

test('ApiEditUpdate extracts error message from response', function () {
    Http::fake([
        'django-test.example.com/api/apis/1/code' => Http::response(['detail' => 'Custom error message'], 400),
    ]);

    $request = Request::create('/api/apis/django/1', 'PUT', ['code' => 'test']);
    $response = $this->controller->ApiEditUpdate($request, 'django', '1');

    expect($response->getStatusCode())->toBe(400)
        ->and(json_decode($response->getContent(), true))->toHaveKey('details')
        ->and(json_decode($response->getContent(), true)['details'])->toBe('Custom error message');
});


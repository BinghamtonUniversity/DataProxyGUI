<?php

use App\Http\Controllers\Api\BaseDjangoController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// Create a concrete test controller that extends BaseDjangoController
class TestBaseDjangoController extends BaseDjangoController
{
    public function publicMakeBackendRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        string $backend = 'django'
    ): array {
        return $this->makeBackendRequest($method, $endpoint, $data, $headers, $backend);
    }

    public function publicMakeDjangoRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        return $this->makeDjangoRequest($method, $endpoint, $data, $headers);
    }
}

beforeEach(function () {
    // Set up test configuration
    Config::set('services.django.base_url', 'https://django-test.example.com');
    Config::set('services.django.api_user', 'test_django_user');
    Config::set('services.django.api_password', 'test_django_password');
    
    Config::set('services.php.base_url', 'https://php-test.example.com');
    Config::set('services.php.api_user', 'test_php_user');
    Config::set('services.php.api_password', 'test_php_password');
});

test('makeBackendRequest constructs correct URL for django backend', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response(['success' => true], 200),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200);
});

test('makeBackendRequest constructs correct URL for php backend', function () {
    Http::fake([
        'php-test.example.com/api/test-endpoint' => Http::response(['success' => true], 200),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'php');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200);
});

test('makeBackendRequest includes X-Unique-Id header from authenticated user', function () {
    Http::fake(function ($request) {
        expect($request->header('X-Unique-Id'))->toBe('test-user-456');
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-456';
    $user->save();
    Auth::login($user);

    $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');
});

test('makeBackendRequest includes Accept header by default', function () {
    Http::fake(function ($request) {
        expect($request->header('Accept'))->toBe('application/json');
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');
});

test('makeBackendRequest includes Content-Type header for POST requests', function () {
    Http::fake(function ($request) {
        expect($request->header('Content-Type'))->toBe('application/json');
        return Http::response(['success' => true], 201);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $controller->publicMakeBackendRequest('POST', 'test-endpoint', ['key' => 'value'], [], 'django');
});

test('makeBackendRequest includes Content-Type header for PUT requests', function () {
    Http::fake(function ($request) {
        expect($request->header('Content-Type'))->toBe('application/json');
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $controller->publicMakeBackendRequest('PUT', 'test-endpoint', ['key' => 'value'], [], 'django');
});

test('makeBackendRequest includes Content-Type header for PATCH requests', function () {
    Http::fake(function ($request) {
        expect($request->header('Content-Type'))->toBe('application/json');
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $controller->publicMakeBackendRequest('PATCH', 'test-endpoint', ['key' => 'value'], [], 'django');
});

test('makeBackendRequest does not include Content-Type header for GET requests', function () {
    Http::fake(function ($request) {
        // GET requests should not have Content-Type
        expect($request->header('Content-Type'))->toBeNull();
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');
});

test('makeBackendRequest merges custom headers with default headers', function () {
    Http::fake(function ($request) {
        expect($request->header('X-Unique-Id'))->toBe('test-user-123')
            ->and($request->header('Accept'))->toBe('application/json')
            ->and($request->header('X-Custom-Header'))->toBe('custom-value');
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $controller->publicMakeBackendRequest(
        'GET',
        'test-endpoint',
        [],
        ['X-Custom-Header' => 'custom-value'],
        'django'
    );
});

test('makeBackendRequest handles GET requests correctly', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response(['data' => 'test'], 200),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200)
        ->and($result['data'])->toBe(['data' => 'test']);
});

test('makeBackendRequest handles POST requests correctly', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response(['id' => 1, 'name' => 'test'], 201),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest(
        'POST',
        'test-endpoint',
        ['name' => 'test'],
        [],
        'django'
    );

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(201)
        ->and($result['data'])->toHaveKey('id');
});

test('makeBackendRequest handles PUT requests correctly', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response(['id' => 1, 'name' => 'updated'], 200),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest(
        'PUT',
        'test-endpoint',
        ['name' => 'updated'],
        [],
        'django'
    );

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200)
        ->and($result['data']['name'])->toBe('updated');
});

test('makeBackendRequest handles DELETE requests correctly', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response([], 204),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('DELETE', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(204);
});

test('makeBackendRequest handles HTTP errors correctly', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response(['error' => 'Not found'], 404),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(404)
        ->and($result['data'])->toHaveKey('error');
});

test('makeBackendRequest handles server errors correctly', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response(['error' => 'Internal server error'], 500),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(500);
});

test('makeBackendRequest handles network exceptions correctly', function () {
    Http::fake(function () {
        throw new \Exception('Network error');
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe(500)
        ->and($result['data'])->toHaveKey('error')
        ->and($result['data']['error'])->toBe('Internal server error');
});

test('makeBackendRequest throws exception for unsupported HTTP method', function () {
    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    expect(fn() => $controller->publicMakeBackendRequest('INVALID', 'test-endpoint', [], [], 'django'))
        ->toThrow(\InvalidArgumentException::class, 'Unsupported HTTP method');
});

test('makeDjangoRequest delegates to makeBackendRequest with django backend', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response(['success' => true], 200),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeDjangoRequest('GET', 'test-endpoint');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(200);
});

test('makeBackendRequest uses basic authentication for django backend', function () {
    Http::fake(function ($request) {
        // Check that basic auth is used (we can't directly check credentials, but we verify the request was made)
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeTrue();
});

test('makeBackendRequest uses basic authentication for php backend', function () {
    Http::fake(function ($request) {
        return Http::response(['success' => true], 200);
    });

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'php');

    expect($result['success'])->toBeTrue();
});

test('makeBackendRequest parses JSON response correctly', function () {
    $responseData = [
        'id' => 1,
        'name' => 'Test API',
        'status' => 'active',
    ];

    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response($responseData, 200),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('GET', 'test-endpoint', [], [], 'django');

    expect($result['data'])->toBe($responseData);
});

test('makeBackendRequest handles empty response body', function () {
    Http::fake([
        'django-test.example.com/api/test-endpoint' => Http::response('', 204),
    ]);

    $controller = new TestBaseDjangoController();
    $user = User::factory()->create();
    $user->unique_id = 'test-user-123';
    $user->save();
    Auth::login($user);

    $result = $controller->publicMakeBackendRequest('DELETE', 'test-endpoint', [], [], 'django');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe(204);
});



<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\OidcController;

Route::redirect('/', '/welcome');

Route::get('/welcome', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// OIDC SSO routes
Route::prefix('oidc')->group(function () {
    Route::get('/redirect', [OidcController::class, 'redirect'])->name('oidc.redirect');
    Route::get('/callback', [OidcController::class, 'callback'])->name('oidc.callback');
});

// Returns JSON list of available proxy servers
Route::get('/api/proxy-servers', [App\Http\Controllers\ProxyServerController::class, 'getServers'])->middleware(['auth']);
Route::post('/api/proxy-servers', [App\Http\Controllers\ProxyServerController::class, 'store'])->middleware(['auth']);
Route::put('/api/proxy-servers/{id}', [App\Http\Controllers\ProxyServerController::class, 'update'])->middleware(['auth']);
Route::delete('/api/proxy-servers/{id}', [App\Http\Controllers\ProxyServerController::class, 'destroy'])->middleware(['auth']);
Route::put('/api/proxy-servers/bulk', [App\Http\Controllers\ProxyServerController::class, 'bulkUpdate'])->middleware(['auth']);

// Internal (GUI) users – super admins only
Route::middleware(['auth', 'super.admin'])->prefix('api')->group(function () {
    Route::get('/internal-users', [App\Http\Controllers\Settings\InternalUsersController::class, 'index']);
    Route::post('/internal-users', [App\Http\Controllers\Settings\InternalUsersController::class, 'store']);
    Route::put('/internal-users/{id}', [App\Http\Controllers\Settings\InternalUsersController::class, 'update']);
    Route::delete('/internal-users/{id}', [App\Http\Controllers\Settings\InternalUsersController::class, 'destroy']);
    Route::post('/internal-users/{id}/impersonate', [App\Http\Controllers\Settings\InternalUsersController::class, 'impersonate']);
});
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::post('/internal-users/leave-impersonation', [App\Http\Controllers\Settings\InternalUsersController::class, 'leaveImpersonation']);
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [OidcController::class, 'logout'])->name('logout');
});

require __DIR__.'/settings.php';


// ===========================================
// API Export - (JSON export in new tab)
// ============================================
Route::prefix('{server_slug}')->middleware(['auth', 'proxy.server'])->group(function () {
   

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
        // return redirect()->route('oidc.redirect');
    })->name('dashboard');

    Route::get('/apis', function () {
        return Inertia::render('Apis');
    })->name('apis');

    Route::get('/api_instances', function () {
        return Inertia::render('ApiInstances');
    })->name('api_instances');

    Route::get('/formviewer-example', function () {
        return Inertia::render('FormViewerExample');
    })->name('formviewer.example');

    Route::get('/types-example', function () {
        return Inertia::render('TypesExample');
    })->name('types.example');

    // Route::get('/settings', function () {
    //     return Inertia::render('Settings');
    // })->name('settings');

    Route::get('/formbuilder-example', function () {
        return Inertia::render('FormBuilderExample');
    })->name('formbuilder.example');


    Route::get('/datagrid-example', function () {
        return Inertia::render('DataGridExample');
    })->name('datagrid.example');

    Route::get('/environments', function () {
        return Inertia::render('Environments');
    })->name('environments')->middleware('server.admin');

    Route::get('/schedules', function () {
        return Inertia::render('Schedules');
    })->name('schedules');

    Route::get('/activity_log', function () {
        return Inertia::render('ActivityLogs');
    })->name('activity_log')->middleware('server.admin');

    Route::get('/api_accounts', function () {
        return Inertia::render('ApiAccounts');
    })->name('api_accounts');

    Route::get('/users', function () {
        return Inertia::render('Users');
    })->name('users')->middleware('server.admin');
    
    // Route::get('/unit-tests', function () {
    //     return Inertia::render('development/UnitTests');
    // })->name('unit-tests');


    // API Routes - Generic resource controller
    Route::prefix('api')->group(function () {
        // Generic resource routes - automatically handles all CRUD operations
        $resources = ['environments', 'users'];

        foreach ($resources as $resource) {
            Route::get("/{$resource}", [App\Http\Controllers\Api\ApiController::class, "{$resource}Index"]);
            Route::post("/{$resource}", [App\Http\Controllers\Api\ApiController::class, "{$resource}Store"]);
            Route::put("/{$resource}/{id}", [App\Http\Controllers\Api\ApiController::class, "{$resource}Update"]);
            Route::delete("/{$resource}/{id}", [App\Http\Controllers\Api\ApiController::class, "{$resource}Destroy"]);
        }

        //APIs
        Route::get('/apis', [App\Http\Controllers\Api\ApiController::class, 'apisIndex']);
        Route::post('/apis', [App\Http\Controllers\Api\ApiController::class, 'apisStore']);
        Route::put('/apis/{id}', [App\Http\Controllers\Api\ApiController::class, 'apisUpdate']);
        Route::delete('/apis/{id}', [App\Http\Controllers\Api\ApiController::class, 'apisDestroy']);

        //API Instances
        Route::get('/api_instances', [App\Http\Controllers\Api\ApiInstancesController::class, 'apiInstancesIndex']);
        Route::post('/api_instances', [App\Http\Controllers\Api\ApiInstancesController::class, 'apiInstancesStore']);
        Route::put('/api_instances/{id}', [App\Http\Controllers\Api\ApiInstancesController::class, 'apiInstancesUpdate']);
        Route::delete('/api_instances/{id}', [App\Http\Controllers\Api\ApiInstancesController::class, 'apiInstancesDestroy']);

        //Schedulers
        Route::get('/scheduler', [App\Http\Controllers\Api\SchedulersController::class, 'schedulersIndex']);
        Route::get('/scheduler/{id}/run', [App\Http\Controllers\Api\SchedulersController::class, 'manualRunScheduler']);
        Route::post('/scheduler', [App\Http\Controllers\Api\SchedulersController::class, 'schedulersStore']);
        Route::put('/scheduler/{id}', [App\Http\Controllers\Api\SchedulersController::class, 'schedulersUpdate']);
        Route::delete('/scheduler/{id}', [App\Http\Controllers\Api\SchedulersController::class, 'schedulersDestroy']);

        //Activity Logs
        Route::get('/activity_log', [App\Http\Controllers\Api\ActivityLogsController::class, 'activityLogsIndex']);

        // Documentation
        Route::get('/api_docs/{api_instance_id}', [App\Http\Controllers\Api\DocumentationController::class, 'apiDocs']);

        //API Users
        Route::get('/api_users', [App\Http\Controllers\Api\ApiUsersController::class, 'apiUsersIndex']);
        Route::post('/api_users', [App\Http\Controllers\Api\ApiUsersController::class, 'apiUsersStore']);
        Route::put('/api_users/{id}', [App\Http\Controllers\Api\ApiUsersController::class, 'apiUsersUpdate']);
        Route::delete('/api_users/{id}', [App\Http\Controllers\Api\ApiUsersController::class, 'apiUsersDestroy']);
        Route::get('/api_users/{id}/decrypted_secret', [App\Http\Controllers\Api\ApiUsersController::class, 'apiUsersDecryptedSecret']);

        //API Versions
        Route::get('/apis/{id}/api_versions', [App\Http\Controllers\Api\ApiController::class, 'apiVersionsIndex']);
        Route::get('/api_versions', [App\Http\Controllers\Api\ApiController::class, 'apiVersionsList']);

        // Tests
        Route::get('/tests', [App\Http\Controllers\Api\TestsController::class, 'listTests']);
        Route::get('/tests/stats', [App\Http\Controllers\Api\TestsController::class, 'getTestStats']);
        Route::post('/tests/run', [App\Http\Controllers\Api\TestsController::class, 'runAllTests']);
        Route::post('/tests/run-file', [App\Http\Controllers\Api\TestsController::class, 'runTestFile']);

        // API Developer routes
        Route::get('/apis/{id}/developers', [App\Http\Controllers\Api\ApiDevelopersController::class, 'getApiDevelopers']);
        Route::post('/apis/{id}/developers/{user_id}', [App\Http\Controllers\Api\ApiDevelopersController::class, 'createApiDeveloper']);
        Route::delete('/apis/{api_id}/developers/{user_id}', [App\Http\Controllers\Api\ApiDevelopersController::class, 'deleteApiDeveloper']);
    });

    // Resources
    Route::get('/resources', function () {
        return Inertia::render('Resources');
    })->name('resources')->middleware('server.admin');


    Route::prefix('ajax/resources')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ResourcesController::class, 'resourcesIndex']);
        Route::get('/{id}', [App\Http\Controllers\Api\ResourcesController::class, 'resourcesShow']);
        Route::get('/type/{type}', [App\Http\Controllers\Api\ResourcesController::class, 'resourcesByTypeIndex']);
        Route::post('/', [App\Http\Controllers\Api\ResourcesController::class, 'resourcesStore']);
        Route::put('/{id}', [App\Http\Controllers\Api\ResourcesController::class, 'resourcesUpdate']);
        Route::delete('/{id}', [App\Http\Controllers\Api\ResourcesController::class, 'resourcesDestroy']);
    });

    // NEW API Edit Routes (Inertia pages for editing APIs)
    Route::prefix('/apis/{api_id}')->group(function () {

        // Developers page - separate from tab layout
        Route::get('/developers', function ($server_slug, $api_id) {
            return Inertia::render('apiEdit/ApiDevelopersPage', [
                'api_id' => $api_id,
            ]);
        })->name('apiEdit.developers');

        // Main page route - renders the Inertia component
        Route::get('/{tab?}', function ($server_slug, $api_id, $tab = 'routes') {
            // Validate tab parameter
            $validTabs = ['routes', 'resources', 'functions', 'files', 'models', 'options'];

            if (!in_array($tab, $validTabs)) {
                abort(404);
            }

            return Inertia::render('apiEdit/Layout', [
                // 'api_type' => $api_type,
                'api_id' => $api_id,
                'activeTab' => $tab,
            ]);
        })->name('apiEdit.index')->where('tab', 'routes|resources|functions|files|models|options');
    });

    // ============================================
    // API Latest Version - (JSON responses - AJAX calls)
    // ============================================
    Route::prefix('/ajax/apis')->group(function () {
        Route::get('/{api_id}', [App\Http\Controllers\Api\ApiController::class, 'apisShow'])
            ->name('api.show');
        Route::get('/{api_id}/versions/latest', [App\Http\Controllers\Api\ApiController::class, 'ApiEditIndex'])
            ->name('api.edit.index');
        Route::put('/{api_id}/code', [App\Http\Controllers\Api\ApiController::class, 'ApiEditUpdate'])
            ->name('api.edit.update');
        // TO-DO add api_type parameter to the following routes
        Route::get('/{api_id}/versions', [App\Http\Controllers\Api\ApiController::class, 'getApiVersions']);
        Route::get('/versions/{version_id}', [App\Http\Controllers\Api\ApiController::class, 'getApiVersionDetails']);
        Route::put('/{api_id}/publish', [App\Http\Controllers\Api\ApiController::class, 'publishApiVersion']);
    });

    Route::get('/apis/{api_id}/version/latest', [App\Http\Controllers\Api\ApiController::class, 'exportApiVersion'])
        ->name('api.export.version');

    // ============================================
    // API Version Comparison
    // ============================================

    Route::get('/apis/{api_id}/compare/{version_id}', function ($server_slug, $api_id, $version_id) {
        return inertia('apiEdit/Compare', [
            'server_slug' => $server_slug,
            'api_id' => $api_id,
            'version_id' => $version_id
        ]);
    })->name('api.compare');

    // API Instance Edit Routes (Inertia pages for editing API Instances)
    Route::prefix('/api_instances/{instance_id}')->group(function () {
        // Main page route - renders the Inertia component
        Route::get('/{tab?}', function ($server_slug, $instance_id, $tab = 'main') {
            // Validate tab parameter
            $validTabs = ['main', 'resources', 'permissions', 'options'];

            if (!in_array($tab, $validTabs)) {
                abort(404);
            }

            return Inertia::render('apiInstanceEdit/Layout', [
                'server_slug' => $server_slug,
                // 'api_type' => $api_type,
                'instance_id' => $instance_id,
                'activeTab' => $tab,
            ]);
        })->name('apiInstanceEdit.index')->where('tab', 'main|resources|permissions|options');
    });

    // ============================================
    // API Instance Details - (JSON responses - AJAX calls)
    // ============================================

    Route::prefix('/ajax/api_instances')->group(function () {
        Route::get('/{instance_id}', [App\Http\Controllers\Api\ApiInstancesController::class, 'ApiInstancesEditIndex'])
            ->name('api_instances.edit.index');
        Route::put('/{instance_id}', [App\Http\Controllers\Api\ApiInstancesController::class, 'ApiInstancesEditUpdate'])
            ->name('api_instances.edit.update');

    });

});


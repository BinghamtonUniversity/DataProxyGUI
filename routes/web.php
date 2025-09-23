
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/apis', function () {
    return Inertia::render('Apis');
})->middleware(['auth', 'verified'])->name('apis');

Route::get('/api_instances', function () {
    return Inertia::render('ApiInstances');
})->middleware(['auth', 'verified'])->name('api_instances');


Route::middleware(['auth', 'verified'])->prefix('apis/{api_type}/{api_id}')->group(function () {
    
    // Developers page - separate from tab layout
    Route::get('/developers', function ($api_type, $api_id) {
        return Inertia::render('apiEdit/ApiDevelopersPage', [
            'api_type' => $api_type,
            'api_id' => $api_id,
        ]);
    })->name('apiEdit.developers');

    // Single route that handles all tabs with optional tab parameter
    Route::get('/{tab?}', function ($api_type, $api_id, $tab = 'routes') {
        // Validate tab parameter
        $validTabs = ['routes', 'resources', 'functions', 'files', 'models', 'options'];
        
        if (!in_array($tab, $validTabs)) {
            abort(404);
        }

        return Inertia::render('apiEdit/Layout', [
            'api_type' => $api_type,
            'api_id' => $api_id,
            'activeTab' => $tab,
        ]);
    })->name('apiEdit.index')->where('tab', 'routes|resources|functions|files|models|options');
});



Route::get('/formviewer-example', function () {
    return Inertia::render('FormViewerExample');
})->middleware(['auth', 'verified'])->name('formviewer.example');

Route::get('/types-example', function () {
    return Inertia::render('TypesExample');
})->middleware(['auth', 'verified'])->name('types.example');

Route::get('/settings', function () {
    return Inertia::render('Settings');
})->middleware(['auth', 'verified'])->name('settings');

Route::get('/formbuilder-example', function () {
    return Inertia::render('FormBuilderExample');
})->middleware(['auth', 'verified'])->name('formbuilder.example');


Route::get('/datagrid-example', function () {
    return Inertia::render('DataGridExample');
})->middleware(['auth', 'verified'])->name('datagrid.example');

Route::get('/environments', function () {
    return Inertia::render('Environments');
})->middleware(['auth', 'verified'])->name('environments');

Route::get('/schedules', function () {
    return Inertia::render('Schedules');
})->middleware(['auth', 'verified'])->name('schedules');

Route::get('/users', function () {
    return Inertia::render('Users');
})->middleware(['auth', 'verified'])->name('users');

Route::get('/cas_users', function () {
    return Inertia::render('CasUsers');
})->middleware(['auth', 'verified'])->name('cas_users');

// API Routes - Generic resource controller
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
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
    Route::get('/api_instances', [App\Http\Controllers\Api\ApiController::class, 'apiInstancesIndex']);
    Route::post('/api_instances', [App\Http\Controllers\Api\ApiController::class, 'apiInstancesStore']);
    Route::put('/api_instances/{id}', [App\Http\Controllers\Api\ApiController::class, 'apiInstancesUpdate']);

    //API Users
    Route::get('/api_users', [App\Http\Controllers\Api\ApiController::class, 'apiUsersIndex']);
    Route::post('/api_users', [App\Http\Controllers\Api\ApiController::class, 'apiUsersStore']);
    Route::put('/api_users/{id}', [App\Http\Controllers\Api\ApiController::class, 'apiUsersUpdate']);
    Route::delete('/api_users/{id}', [App\Http\Controllers\Api\ApiController::class, 'apiUsersDestroy']);

    //API Versions
    Route::get('/apis/{id}/api_versions', [App\Http\Controllers\Api\ApiController::class, 'apiVersionsIndex']);

    // API Developer routes
    Route::get('/apis/{id}/developers', [App\Http\Controllers\Api\ApiController::class, 'getApiDevelopers']);
    Route::post('/apis/{id}/developers', [App\Http\Controllers\Api\ApiController::class, 'createApiDeveloper']);
    Route::put('/apis/{api_id}/developers/{id}', [App\Http\Controllers\Api\ApiController::class, 'updateApiDeveloper']);
    Route::delete('/apis/{api_id}/developers/{id}', [App\Http\Controllers\Api\ApiController::class, 'deleteApiDeveloper']);

});

// Resources


Route::get('/resources', function () {
    return Inertia::render('Resources');
})->middleware(['auth', 'verified'])->name('resources');


Route::middleware(['auth', 'verified'])->prefix('ajax/resources')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ApiController::class, 'resourcesIndex']);
    Route::get('/{id}', [App\Http\Controllers\Api\ApiController::class, 'resourcesShow']);
    Route::get('/type/{type}', [App\Http\Controllers\Api\ApiController::class, 'resourcesByTypeIndex']);
    Route::post('/', [App\Http\Controllers\Api\ApiController::class, 'resourcesStore']);
    Route::put('/{id}', [App\Http\Controllers\Api\ApiController::class, 'resourcesUpdate']);
    Route::delete('/{id}', [App\Http\Controllers\Api\ApiController::class, 'resourcesDestroy']);

});


// API Edit Routes (Inertia pages for editing APIs)
Route::middleware(['auth', 'verified'])->prefix('/apis/{api_type}/{api_id}')->group(function () {    
    // Main page route - renders the Inertia component
    Route::get('/{tab?}', function ($api_type, $api_id, $tab = 'routes') {
        // Validate tab parameter
        $validTabs = ['routes', 'resources', 'functions', 'files', 'models', 'options'];
        
        if (!in_array($tab, $validTabs)) {
            abort(404);
        }

        return Inertia::render('apiEdit/Layout', [
            'api_type' => $api_type,
            'api_id' => $api_id,
            'activeTab' => $tab,
        ]);
    })->name('apiEdit.index')->where('tab', 'routes|resources|functions|files|models|options');   
});

// ============================================
// API Latest Version - (JSON responses - AJAX calls)
// ============================================  
Route::middleware(['auth', 'verified'])->prefix('/ajax/apis')->group(function () {
    Route::get('/{api_id}/versions/latest', [App\Http\Controllers\Api\ApiController::class, 'ApiEditIndex'])
        ->name('api.edit.index');
    Route::put('/{api_id}/code', [App\Http\Controllers\Api\ApiController::class, 'ApiEditUpdate'])
        ->name('api.edit.update');
});


// API Instance Edit Routes (Inertia pages for editing API Instances)
Route::middleware(['auth', 'verified'])->prefix('/api_instances/{instance_id}')->group(function () {    
    // Main page route - renders the Inertia component
    Route::get('/{tab?}', function ($instance_id, $tab = 'main') {
        // Validate tab parameter
        $validTabs = ['main', 'resources', 'permissions', 'options'];
        
        if (!in_array($tab, $validTabs)) {
            abort(404);
        }

        return Inertia::render('apiInstanceEdit/Layout', [
            'instance_id' => $instance_id,
            'activeTab' => $tab,
        ]);
    })->name('apiInstanceEdit.index')->where('tab', 'main|resources|permissions|options');   
});

// ============================================
// API Instance Details - (JSON responses - AJAX calls)
// ============================================
    
Route::middleware(['auth', 'verified'])->prefix('/ajax/api_instances')->group(function () {
    Route::get('/{instance_id}', [App\Http\Controllers\Api\ApiController::class, 'ApiInstancesEditIndex'])
        ->name('api_instances.edit.index');
    Route::put('/{instance_id}', [App\Http\Controllers\Api\ApiController::class, 'ApiInstancesEditUpdate'])
        ->name('api_instances.edit.update');

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

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

// Route::middleware(['auth', 'verified'])->prefix('apis/{api_type}/{api_id}')->group(function () {
//     Route::get('/routes', function ($api_type, $api_id) {
//         return Inertia::render('apiEdit/Routes', [
//             'api_type' => $api_type,
//             'api_id' => $api_id,
//         ]);
//     })->name('apiEdit.routes');

//     Route::get('/resources', function ($api_type, $api_id) {
//         return Inertia::render('apiEdit/Resources',[
//             'api_type' => $api_type,
//             'api_id' => $api_id,
//         ]);
//     })->name('apiEdit.resources');

//     Route::get('/functions', function ($api_type, $api_id) {
//         return Inertia::render('apiEdit/Functions',[
//             'api_type' => $api_type,
//             'api_id' => $api_id,
//         ]);
//     })->name('apiEdit.functions');

//     Route::get('/files', function ($api_type, $api_id ) {
//         return Inertia::render('apiEdit/Files', [
//             'api_type' => $api_type,
//             'api_id' => $api_id,
//         ]);
//     })->name('apiEdit.files');

//     Route::get('/models', function ($api_type, $api_id ) {
//         return Inertia::render('apiEdit/Models', [
//             'api_type' => $api_type,
//             'api_id' => $api_id,
//         ]);
//     })->name('apiEdit.models');

//     Route::get('/options', function ($api_type, $api_id) {
//         return Inertia::render('apiEdit/Options', [
//             'api_type' => $api_type,
//             'api_id' => $api_id,
//         ]);
//     })->name('apiEdit.options');
// });

// Route::get('/editor', function () {
//     return Inertia::render('Editor');
// })->middleware(['auth', 'verified'])->name('editor');

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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
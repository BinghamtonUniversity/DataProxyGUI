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

// Route::get('/apis/{id}', function ($id) {
//     return Inertia::render('apiEdit/ApiEditSidebar', ['id' => $id]);
// })->middleware(['auth', 'verified'])->name('api.edit');


Route::middleware(['auth', 'verified'])->prefix('apis/{api_id}')->group(function () {
    Route::get('/routes', function ($api_id) {
        return Inertia::render('apiEdit/Routes', ['api_id' => $api_id]);
    })->name('apiEdit.routes');

    Route::get('/resources', function ($api_id) {
        return Inertia::render('apiEdit/Resources',['api_id' => $api_id]);
    })->name('apiEdit.resources');

    Route::get('/functions', function ($api_id) {
        return Inertia::render('apiEdit/Functions',['api_id' => $api_id]);
    })->name('apiEdit.functions');

    Route::get('/files', function ($api_id) {
        return Inertia::render('apiEdit/Files', ['api_id' => $api_id]);
    })->name('apiEdit.files');

    Route::get('/options', function ($api_id) {
        return Inertia::render('apiEdit/Options', ['api_id' => $api_id]);
    })->name('apiEdit.options');
});

Route::get('/editor', function () {
    return Inertia::render('Editor');
})->middleware(['auth', 'verified'])->name('editor');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
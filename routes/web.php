
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

Route::get('/apis/{id}', function ($id) {
    return Inertia::render('ApiEdit', ['id' => $id]);
})->middleware(['auth', 'verified'])->name('api.edit');

Route::get('/editor', function () {
    return Inertia::render('Editor');
})->middleware(['auth', 'verified'])->name('editor');

Route::get('/formviewer-example', function () {
    return Inertia::render('FormViewerExample');
})->middleware(['auth', 'verified'])->name('formviewer.example');
Route::get('/types-example', function () {
    return Inertia::render('TypesExample');
})->middleware(['auth', 'verified'])->name('types.example');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
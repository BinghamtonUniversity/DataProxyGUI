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


Route::get('/editor', function () {
    return Inertia::render('Editor');
})->middleware(['auth', 'verified'])->name('editor');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

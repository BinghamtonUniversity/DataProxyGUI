<?php

use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/settings/no-servers-available', function () {
        return Inertia::render('NoServersAvailable');
    });

Route::middleware('auth')->group(function () {

    Route::get('/settings', [ProfileController::class, 'edit']);
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
    Route::get('/settings/servers', function () {
        return Inertia::render('settings/Servers');
    })->name('servers')->middleware('can:manage_users');

    Route::get('/settings/users', function () {
        return Inertia::render('settings/InternalUsers');
    })->name('users')->middleware('can:manage_users');;

    Route::get('/settings/no-servers-available', function () {
        return Inertia::render('NoServerAvailable');
    })->name('no-servers-available');

    Route::get('/{server_slug?}/settings/profile', [ProfileController::class, 'edit'])->name('slug.profile.edit');
    Route::patch('/{server_slug?}/settings/profile', [ProfileController::class, 'update'])->name('slug.profile.update');

    Route::get('/{server_slug?}/settings/appearance', function ($server_slug = null) {
        return Inertia::render('settings/Appearance', ['server_slug' => $server_slug]);
    })->name('slug.appearance');
    
    Route::get('/{server_slug?}/settings/servers', function ($server_slug = null) {
        return Inertia::render('settings/Servers', ['server_slug' => $server_slug]);
    })->name('slug.servers')->middleware('can:manage_users');

    Route::get('/{server_slug?}/settings/users', function ($server_slug = null) {
        return Inertia::render('settings/InternalUsers', ['server_slug' => $server_slug]);
    })->name('slug.users')->middleware('can:manage_users');
});

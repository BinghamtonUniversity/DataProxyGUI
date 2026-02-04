<?php

use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/settings/no-servers-available', function () {
        return Inertia::render('NoServersAvailable');
    })->name('no-servers-available');

Route::middleware('auth')->group(function () {
    // Route::redirect('{server_slug?}/settings', '/{server_slug?}/settings/profile');

    // Route::get('/settings', function () {
    //     return redirect('/settings/profile');
    // });
    
    // Route::get('/{server_slug}/settings', function ($server_slug) {
    //     return redirect("/{$server_slug}/settings/profile");
    // });

    Route::get('/settings', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
    Route::get('/settings/servers', function () {
        return Inertia::render('settings/Servers');
    })->name('servers');

    Route::get('/settings/no-servers-available', function () {
        return Inertia::render('NoServerAvailable');
    })->name('no-servers-available');

    Route::get('/{server_slug?}/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/{server_slug?}/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
//    Route::delete('{server_slug?}/settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/{server_slug?}/settings/appearance', function ($server_slug = null) {
        return Inertia::render('settings/Appearance', ['server_slug' => $server_slug]);
    })->name('appearance');
    
    Route::get('/{server_slug?}/settings/servers', function ($server_slug = null) {
        return Inertia::render('settings/Servers', ['server_slug' => $server_slug]);
    })->name('servers');
});

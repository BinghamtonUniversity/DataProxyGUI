<?php

use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('{server_slug?}/settings', '/{server_slug?}/settings/profile');

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

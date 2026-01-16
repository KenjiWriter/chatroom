<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/chat', [\App\Http\Controllers\RoomController::class, 'index'])->name('chat.index');
    Route::get('/chat/{room:slug}', [\App\Http\Controllers\RoomController::class, 'show'])->name('chat.room');
    Route::post('/chat/{room:slug}/message', [\App\Http\Controllers\RoomController::class, 'storeMessage'])->name('chat.message');
    
    // Moderation
    Route::post('/admin/kick/{room}/{user}', [\App\Http\Controllers\ModerationController::class, 'kick'])->name('admin.kick');
    Route::post('/admin/mute/{user}', [\App\Http\Controllers\ModerationController::class, 'mute'])->name('admin.mute');
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'verified']], function () {
    Route::resource('ranks', \App\Http\Controllers\Admin\RankController::class);
});

require __DIR__.'/settings.php';

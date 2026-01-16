<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/chat', [\App\Http\Controllers\RoomController::class, 'index'])->name('chat.index');
    Route::get('/chat/{room:slug}', [\App\Http\Controllers\RoomController::class, 'show'])->name('chat.room');
    Route::post('/chat/{room:slug}/message', [\App\Http\Controllers\RoomController::class, 'storeMessage'])->name('chat.message');
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'verified']], function () {
    Route::resource('ranks', \App\Http\Controllers\Admin\RankController::class);
});

require __DIR__.'/settings.php';

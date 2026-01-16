<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\ConversationController;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/chat', [\App\Http\Controllers\RoomController::class, 'index'])->name('chat.index');
    Route::get('/chat/{room:slug}', [\App\Http\Controllers\RoomController::class, 'show'])->name('chat.room')->middleware(['room.access']);
    Route::post('/chat/{room:slug}/message', [\App\Http\Controllers\RoomController::class, 'storeMessage'])->name('chat.message')->middleware(['room.ban']);
    
    // Moderation
    Route::post('/admin/kick/{room}/{user}', [\App\Http\Controllers\ModerationController::class, 'kick'])->name('admin.kick');
    Route::post('/admin/mute/{user}', [\App\Http\Controllers\ModerationController::class, 'mute'])->name('admin.mute');
    Route::post('/admin/ban/{user}', [\App\Http\Controllers\ModerationController::class, 'ban'])->name('admin.ban');

    // Social
    Route::get('/api/users/{user}/hover-card', [\App\Http\Controllers\UserController::class, 'hoverCard'])->name('users.hover-card');
    Route::post('/friendships/{user}', [\App\Http\Controllers\FriendshipController::class, 'store'])->name('friendships.store');
    Route::put('/friendships/{id}', [\App\Http\Controllers\FriendshipController::class, 'update'])->name('friendships.update');
    Route::delete('/friendships/{id}', [\App\Http\Controllers\FriendshipController::class, 'destroy'])->name('friendships.destroy');

    // Conversations (DMs)
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/{conversation}/messages', [ConversationController::class, 'sendMessage'])->name('conversations.messages.store');
        Route::post('/{conversation}/read', [ConversationController::class, 'markAsRead'])->name('conversations.read');
    });
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/settings', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.settings');
    Route::resource('ranks', \App\Http\Controllers\Admin\RankController::class);
    Route::resource('rooms', \App\Http\Controllers\Admin\RoomController::class);

    // User Management
    Route::post('/users/{user}/rank', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateRank'])->name('admin.users.rank');
    Route::get('/api/ranks/assignable', [\App\Http\Controllers\Admin\UserManagementController::class, 'assignableRanks'])->name('admin.ranks.assignable');
});

require __DIR__.'/settings.php';

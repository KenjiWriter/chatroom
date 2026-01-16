<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.room.{id}', function (User $user, int $id) {
    if ($user->hasPermission('admin.access')) {
        return true;
    }

    $room = Room::find($id);

    return $room && $room->checkAccess($user);
});

Broadcast::channel('chat.room.presence.{id}', function (User $user, int $id) {
    if ($user->hasPermission('admin.access')) {
        return $user->toArray();
    }

    $room = Room::find($id);

    if ($room && $room->checkAccess($user)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'rank_data' => $user->rank_data, // Use accessor for frontend display
        ];
    }

    return false;
});

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FriendshipService;
use App\Services\LevelService;
use Illuminate\Http\Request;

use App\Services\ModerationService;

class UserController extends Controller
{
    public function hoverCard(User $user, Request $request, LevelService $levelService, FriendshipService $friendshipService, ModerationService $moderationService)
    {
        $currentUser = $request->user();
        
        // Privacy Check
        $areFriends = $currentUser ? $friendshipService->areFriends($currentUser, $user) : false;
        $isSelf = $currentUser && $currentUser->id === $user->id;
        
        $showDetails = !$user->is_private || $areFriends || $isSelf || ($currentUser && $currentUser->hasPermission('admin.access'));

        // Relationship Status
        $friendshipStatus = 'none';
        if ($currentUser && !$isSelf) {
            $friendship = \App\Models\Friendship::where(function($q) use ($currentUser, $user) {
                $q->where('user_id', $currentUser->id)->where('friend_id', $user->id);
            })->orWhere(function($q) use ($currentUser, $user) {
                $q->where('user_id', $user->id)->where('friend_id', $currentUser->id);
            })->first();

            if ($friendship) {
                $friendshipStatus = $friendship->status;
                if ($friendshipStatus === 'pending') {
                    // Distinguish if we sent it or received it
                    $friendshipStatus = ($friendship->user_id === $currentUser->id) ? 'sent_pending' : 'received_pending';
                }
            }
        }

        // Check Mute Status
        $roomId = $request->query('room_id');
        $room = $roomId ? \App\Models\Room::find($roomId) : null;
        $isMuted = $moderationService->isMuted($user, $room);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'banner_url' => $user->banner_url,
            'rank_data' => $user->rank_data,
            'level' => $user->level,
            // Conditional Data
            'bio' => $showDetails ? $user->bio : null,
            'xp' => $showDetails ? $user->xp : null,
            'next_level_xp' => $showDetails ? $levelService->xpForLevel($user->level + 1) : null,
            'created_at' => $user->created_at->format('M Y'),
            
            // Context
            'is_self' => $isSelf,
            'friendship_status' => $friendshipStatus,
            'is_muted' => $isMuted,
        ]);
    }
}

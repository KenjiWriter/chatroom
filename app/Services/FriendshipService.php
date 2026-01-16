<?php

namespace App\Services;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FriendshipService
{
    /**
     * Send a friend request.
     */
    public function sendRequest(User $sender, User $recipient): Friendship
    {
        if ($sender->id === $recipient->id) {
            throw new \Exception("Cannot friend yourself.");
        }

        // Check if request already exists in either direction
        $exists = Friendship::where(function ($q) use ($sender, $recipient) {
            $q->where('user_id', $sender->id)->where('friend_id', $recipient->id);
        })->orWhere(function ($q) use ($sender, $recipient) {
            $q->where('user_id', $recipient->id)->where('friend_id', $sender->id);
        })->first();

        if ($exists) {
            if ($exists->status === 'blocked') {
                throw new \Exception("Unable to send request."); 
            }
            if ($exists->status === 'accepted') {
                throw new \Exception("Already friends.");
            }
            if ($exists->status === 'pending') {
                throw new \Exception("Request already pending.");
            }
        }

        return Friendship::create([
            'user_id' => $sender->id,
            'friend_id' => $recipient->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Accept a friend request.
     */
    public function acceptRequest(User $recipient, int $friendshipId): bool
    {
        $friendship = Friendship::where('id', $friendshipId)
            ->where('friend_id', $recipient->id)
            ->where('status', 'pending')
            ->firstOrFail();

        return $friendship->update(['status' => 'accepted']);
    }

    /**
     * Decline or Cancel a request.
     */
    public function declineRequest(User $user, int $friendshipId): bool
    {
        $friendship = Friendship::where('id', $friendshipId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('friend_id', $user->id);
            })
            ->firstOrFail();

        return $friendship->delete();
    }

    /**
     * Block a user.
     */
    public function blockUser(User $blocker, User $target): Friendship
    {
        // Find existing relationship or create new one
        $friendship = Friendship::where(function ($q) use ($blocker, $target) {
            $q->where('user_id', $blocker->id)->where('friend_id', $target->id);
        })->orWhere(function ($q) use ($blocker, $target) {
            $q->where('user_id', $target->id)->where('friend_id', $blocker->id);
        })->first();

        if ($friendship) {
            // Update to blocked, ensure user_id is blocker for clarity on who blocked whom?
            // Actually, if A blocked B, we usually want a record where user_id=A, friend_id=B, status=blocked.
            // If the row was B->A, we might need to recreate it or just set status.
            // For simplicity, let's just update status. 
            // Ideally we'd track "blocked_by".
            // Since our schema is simple, let's just update and assume the blocker is the one initiating the action? 
            // No, that's ambiguous. 
            // Let's delete strictly and create a new row: user_id=BlockingUser, friend_id=BlockedUser, status='blocked'.
            $friendship->delete();
        }

        return Friendship::create([
            'user_id' => $blocker->id,
            'friend_id' => $target->id,
            'status' => 'blocked'
        ]);
    }

    /**
     * Get all friends (bi-directional logic).
     */
    public function getFriends(User $user): Collection
    {
        // Friends are those where (user_id = me AND status = accepted) OR (friend_id = me AND status = accepted)
        // We want to return User models.
        
        $sentIds = $user->sentFriendships()->where('status', 'accepted')->pluck('friend_id');
        $receivedIds = $user->receivedFriendships()->where('status', 'accepted')->pluck('user_id');
        
        $friendIds = $sentIds->merge($receivedIds)->unique();
        
        return User::whereIn('id', $friendIds)->get();
    }

    /**
     * Check Are Friends
     */
    public function areFriends(User $a, User $b): bool
    {
        return Friendship::where('status', 'accepted')
            ->where(function ($q) use ($a, $b) {
                $q->where('user_id', $a->id)->where('friend_id', $b->id);
            })
            ->orWhere(function ($q) use ($a, $b) {
                $q->where('user_id', $b->id)->where('friend_id', $a->id);
            })
            ->exists();
    }
}

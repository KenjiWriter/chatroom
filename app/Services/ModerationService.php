<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\UserPunished;
use App\Models\Ban;
use App\Models\Message;
use App\Models\Mute;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;

class ModerationService
{
    /**
     * Ensure the moderator has higher priority than the target.
     */
    public function ensureCanModerate(User $moderator, User $target): void
    {
        // Simple hierarchy: Rank Priority.
        // If target has no rank, mod can moderate (assuming mod has rank or is admin).
        // If mod has no rank, they shouldn't be here (permission check handles entry).
        
        if ($target->rank && $moderator->rank) {
            if ($target->rank->priority >= $moderator->rank->priority) {
                // Prevent action if target is equal or higher
                abort(403, 'You cannot moderate a user with equal or higher rank.');
            }
        }
    }

    public function kick(User $moderator, User $target, Room $room, string $reason): void
    {
        $this->ensureCanModerate($moderator, $target);

        // Broadcast Punished Event
        broadcast(new UserPunished($target->id, 'kick', $reason));

        // System Message
        $this->createSystemMessage($room, "{$target->name} was kicked by {$moderator->name}. Reason: {$reason}");
    }

    public function mute(User $moderator, User $target, ?Room $room, ?int $minutes, string $reason): void
    {
        $this->ensureCanModerate($moderator, $target);

        $expiresAt = $minutes ? now()->addMinutes($minutes) : null;

        Mute::create([
            'user_id' => $target->id,
            'moderator_id' => $moderator->id,
            'room_id' => $room?->id, // Null = Global
            'expires_at' => $expiresAt,
            'reason' => $reason,
        ]);

        // Broadcast Punished Event
        broadcast(new UserPunished(
            $target->id, 
            'mute', 
            $reason,
            $minutes ? "{$minutes} minutes" : "permanently",
            $expiresAt?->toIso8601String()
        ));

        // System Message
        $scope = $room ? "in this room" : "globally";
        $duration = $minutes ? "for {$minutes} minutes" : "permanently";
        $msg = "{$target->name} was muted {$scope} by {$moderator->name} {$duration}. Reason: {$reason}";

        if ($room) {
             $this->createSystemMessage($room, $msg);
        } else {
            // If global, maybe notify all active rooms? Or just don't span system messages?
            // For now, let's not spam all rooms.
        }
    }

    public function ban(User $moderator, User $target, ?int $minutes, string $reason): void
    {
        $this->ensureCanModerate($moderator, $target);

        $expiresAt = $minutes ? now()->addMinutes($minutes) : null;

        // Ban User & IP
        Ban::create([
            'user_id' => $target->id,
            'moderator_id' => $moderator->id,
            'ip_address' => request()->ip(), 
            'expires_at' => $expiresAt,
            'reason' => $reason,
        ]);

        broadcast(new UserPunished(
            $target->id, 
            'ban', 
            $reason,
            $minutes ? "{$minutes} minutes" : "permanently",
            $expiresAt?->toIso8601String()
        ));
    }

    public function isMuted(User $user, ?Room $room = null): bool
    {
        return Mute::active()
            ->where('user_id', $user->id)
            ->where(function ($q) use ($room) {
                $q->whereNull('room_id'); // Global
                if ($room) {
                    $q->orWhere('room_id', $room->id);
                }
            })
            ->exists();
    }

    protected function createSystemMessage(Room $room, string $content): void
    {
        $message = $room->messages()->create([
            'user_id' => auth()->id() ?? 1, 
            'content' => $content,
            'is_system_message' => true
        ]);
        
        broadcast(new MessageSent($message))->toOthers();
    }
}

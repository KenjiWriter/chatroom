<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DirectMessage $message)
    {
        $this->message->load('sender');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.room.' . $this->message->conversation_id),
            // Also notify the recipient globally
            new PrivateChannel('conversations.' . $this->getRecipientId()),
        ];
    }

    protected function getRecipientId()
    {
        return $this->message->conversation->users()
            ->where('users.id', '!=', $this->message->sender_id)
            ->first()->id;
    }
}

<?php

namespace App\Events;

use App\Models\Rank;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPromoted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $newRank;
    public $oldRank;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Rank $newRank, ?Rank $oldRank = null)
    {
        $this->user = $user;
        $this->newRank = $newRank;
        $this->oldRank = $oldRank;
    }
}

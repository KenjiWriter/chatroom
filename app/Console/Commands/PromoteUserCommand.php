<?php

namespace App\Console\Commands;

use App\Events\UserPromoted;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:promote {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a user to the next available rank.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $currentPriority = $user->rank?->priority ?? 0;

        $nextRank = Rank::where('priority', '>', $currentPriority)
            ->orderBy('priority', 'asc')
            ->first();

        if (!$nextRank) {
            $this->info("User '{$user->name}' is already at the highest rank.");
            return 0;
        }

        $oldRank = $user->rank;
        $user->rank_id = $nextRank->id;
        $user->save();

        event(new UserPromoted($user, $nextRank, $oldRank));

        $this->info("Successfully promoted user '{$user->name}' to '{$nextRank->name}'.");
        return 0;
    }
}

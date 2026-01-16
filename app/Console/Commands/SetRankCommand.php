<?php

namespace App\Console\Commands;

use App\Models\Rank;
use App\Models\User;
use Illuminate\Console\Command;

class SetRankCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-rank {email} {rank_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually set a user\'s rank by email.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $rankName = $this->argument('rank_name');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $rank = Rank::where('name', $rankName)->first();
        if (!$rank) {
            $this->error("Rank '{$rankName}' not found.");
            return 1;
        }

        $user->rank_id = $rank->id;
        $user->save();

        $this->info("Successfully set rank '{$rankName}' for user '{$user->name}' ({$email}).");
        return 0;
    }
}

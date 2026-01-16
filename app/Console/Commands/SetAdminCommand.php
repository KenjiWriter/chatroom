<?php

namespace App\Console\Commands;

use App\Models\Rank;
use App\Models\User;
use Illuminate\Console\Command;

class SetAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-admin {email}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Assign the Administrator rank to a user by their email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email [{$email}] not found.");
            return 1;
        }

        $adminRank = Rank::where('name', 'Admin')->first();

        if (!$adminRank) {
            $this->error("Admin rank not found in the database. Please ensure you have run the seeders.");
            return 1;
        }

        $user->update([
            'rank_id' => $adminRank->id
        ]);

        $this->info("User [{$email}] is now an Administrator.");

        return 0;
    }
}

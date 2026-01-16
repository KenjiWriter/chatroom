<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Console\Command;

class VerifyUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:verify {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually verify a user email and trigger role upgrade';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found: {$email}");
            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("User {$email} is already verified.");
            return;
        }

        $user->markEmailAsVerified();
        
        // Dispatch the event so our listener upgrades the rank
        event(new Verified($user));

        $this->info("User {$email} verified successfully and upgrade event dispatched.");
    }
}

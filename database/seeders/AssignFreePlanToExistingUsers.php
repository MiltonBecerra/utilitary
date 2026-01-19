<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subscription;
use Carbon\Carbon;

class AssignFreePlanToExistingUsers extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all users who don't have any subscription
        $usersWithoutSubscription = User::whereDoesntHave('subscriptions')->get();

        $count = 0;
        foreach ($usersWithoutSubscription as $user) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_type' => 'free',
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addYear(),
            ]);
            $count++;
        }

        $this->command->info("Assigned free plan to {$count} existing users.");
    }
}

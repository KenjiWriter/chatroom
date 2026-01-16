<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Guest Welcome Center',
                'slug' => 'guest-room',
                'description' => 'Welcome! Please verify your email to unlock other rooms.',
                'min_level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'General Lobby',
                'slug' => 'general',
                'description' => 'A place for everyone to chat and hang out.',
                'min_level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Tech Talk',
                'slug' => 'tech-talk',
                'description' => 'Networking, coding, and everything tech.',
                'min_level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'VIP Lounge',
                'slug' => 'vip-lounge',
                'description' => 'Exclusive chat for high-level members.',
                'min_level' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Admin HQ',
                'slug' => 'admin-hq',
                'description' => 'Top secret administrative discussions.',
                'min_level' => 0,
                'is_active' => true,
                // Assuming we might attach a required_rank_id later manually or via logic
                // 'required_rank_id' => ... 
            ]
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(
                ['slug' => $room['slug']],
                $room
            );
        }
    }
}

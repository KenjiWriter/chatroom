<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RankPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to allow truncation if needed, though usually not needed if we just create or update
        // Using updateOrCreate is better than truncate for seeders usually to avoid wiping dev data accidentally,
        // but for initial setup, we want clean slate for structure.
        // Let's use firstOrCreate/updateOrCreate.

        // 1. Create Permissions
        $permissions = [
            ['slug' => 'ban_user', 'name' => 'Ban User'],
            ['slug' => 'unmute_user', 'name' => 'Unmute User'],
            ['slug' => 'view_logs', 'name' => 'View Logs'],
            ['name' => 'Can Chat', 'slug' => 'chat.write'],
            ['name' => 'View Chat', 'slug' => 'chat.read'],
            ['name' => 'Kick Users', 'slug' => 'kick_user'],
            ['name' => 'Mute Temporarily', 'slug' => 'mute_temp'],
            ['name' => 'Mute Permanently', 'slug' => 'mute_perm'],
            ['name' => 'Restrict Room Access', 'slug' => 'ban_room_access'],
            ['name' => 'Manage Rooms', 'slug' => 'manage_rooms'],
            ['name' => 'Manage Ranks', 'slug' => 'manage_ranks'],
            ['name' => 'Manage User Ranks', 'slug' => 'manage_user_ranks'],
            ['name' => 'Bypass Level Lock', 'slug' => 'bypass.level_lock'],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['slug']] = Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                ['name' => $perm['name']]
            );
        }

        // 2. Create Ranks
        $adminRank = Rank::updateOrCreate(
            ['name' => 'Admin'],
            [
                'prefix' => 'Admin',
                'color_prefix' => '#ff0000',
                'color_name' => '#ff0000',
                'color_text' => '#ffffff',
                'priority' => 100,
                'effects' => [
                    'glow' => true,
                    'rainbow' => true,
                    'bold' => true,
                    'italic' => false,
                ],
            ]
        );

        // Create Moderator Rank
        $moderatorRank = Rank::updateOrCreate(
            ['name' => 'Moderator'],
            [
                'prefix' => 'Mod',
                'color_prefix' => '#007bff', // Blue
                'color_name' => '#007bff',
                'color_text' => '#ffffff',
                'priority' => 50,
                'effects' => [
                    'glow' => false,
                    'rainbow' => false,
                    'bold' => true,
                    'italic' => false,
                ],
            ]
        );

        $userRank = Rank::updateOrCreate(
            ['name' => 'User'],
            [
                'prefix' => null,
                'color_prefix' => '#9ca3af', // Gray 400
                'color_name' => '#4b5563',   // Gray 600
                'color_text' => '#374151',   // Gray 700
                'priority' => 1,
                'effects' => [
                    'glow' => false,
                    'rainbow' => false,
                    'bold' => false,
                    'italic' => false,
                ],
            ]
        );

        // 3. Assign Permissions
        // Admin gets all permissions
        $adminRank->permissions()->sync(Permission::all()->pluck('id'));

        // User gets basic chat permissions
        $userRank->permissions()->sync([
            $permissionModels['chat.write']->id,
            $permissionModels['chat.read']->id,
        ]);

        // Moderator Gets Moderation Permissions
        $modPermissions = [
            'chat.write', 'chat.read',
            'kick_user', 'mute_temp', 'mute_perm', 'unmute_user', 'ban_room_access',
            'view_logs',
        ];
        
        $modPermIds = [];
        foreach ($modPermissions as $slug) {
            if (isset($permissionModels[$slug])) {
                $modPermIds[] = $permissionModels[$slug]->id;
            }
        }
        $moderatorRank->permissions()->sync($modPermIds);

        // 4. Set default rank for users without one
        User::whereNull('rank_id')->update(['rank_id' => $userRank->id]);
    }
}

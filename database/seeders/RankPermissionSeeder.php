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
            ['name' => 'Can Chat', 'slug' => 'chat.write'],
            ['name' => 'View Chat', 'slug' => 'chat.read'],
            ['name' => 'Kick Users', 'slug' => 'user.kick'],
            ['name' => 'Ban Users', 'slug' => 'user.ban'],
            ['name' => 'Mute Users', 'slug' => 'user.mute'],
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
                'prefix' => '[Admin]',
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

        $userRank = Rank::updateOrCreate(
            ['name' => 'User'],
            [
                'prefix' => null,
                'color_prefix' => '#cccccc',
                'color_name' => '#ffffff',
                'color_text' => '#dddddd',
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

        // 4. Set default rank for users without one
        User::whereNull('rank_id')->update(['rank_id' => $userRank->id]);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $playerRole = Role::firstOrCreate(['name' => RoleName::Player->value]);
        Role::firstOrCreate(['name' => RoleName::Admin->value]);

        $testUser = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $testUser->assignRole($playerRole);

        User::factory()->count(14)->create()->each(fn (User $user) => $user->assignRole($playerRole));
    }
}

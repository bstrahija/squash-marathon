<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        $adminRole = Role::firstOrCreate(['name' => RoleName::Admin->value]);

        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminFirstName = env('ADMIN_FIRST_NAME');
        $adminLastName = env('ADMIN_LAST_NAME');

        if (filled($adminEmail) && filled($adminPassword) && filled($adminFirstName) && filled($adminLastName)) {
            $adminUser = User::query()->updateOrCreate(
                ['email' => (string) $adminEmail],
                [
                    'first_name' => (string) $adminFirstName,
                    'last_name' => (string) $adminLastName,
                    'password' => Hash::make((string) $adminPassword),
                ],
            );

            $adminUser->assignRole([$playerRole, $adminRole]);
        }

        $seedUserPassword = (string) config('app.seed_user_password', 'password');

        $players = [
            ['first_name' => 'Igor', 'last_name' => 'Levak', 'email' => 'igor.levak@example.com', 'is_admin' => false],
            ['first_name' => 'Andrija', 'last_name' => 'Munđar', 'email' => 'andrija.mundar@example.com', 'is_admin' => false],
            ['first_name' => 'Tomislav', 'last_name' => 'Pavlic', 'email' => 'tomislav.pavlic@example.com', 'is_admin' => false],
            ['first_name' => 'Damjan', 'last_name' => 'Petričević', 'email' => 'damjan.petricevic@example.com', 'is_admin' => false],
            ['first_name' => 'Dalibor', 'last_name' => 'Strišković', 'email' => 'dalibor.striskovic@example.com', 'is_admin' => false],
            ['first_name' => 'Darko', 'last_name' => 'Terek', 'email' => 'darko.terek@example.com', 'is_admin' => false],
            ['first_name' => 'Matija', 'last_name' => 'Terek', 'email' => 'matija.terek@example.com', 'is_admin' => false],
            ['first_name' => 'Igor', 'last_name' => 'Vibović', 'email' => 'igor.vibovic@example.com', 'is_admin' => false],
            ['first_name' => 'Ivan', 'last_name' => 'Zlatarek', 'email' => 'ivan.zlatarek@example.com', 'is_admin' => false],
            ['first_name' => 'Nebojša', 'last_name' => 'Mikulić', 'email' => 'nebojsa.mikulic@example.com', 'is_admin' => false],
            ['first_name' => 'Boris', 'last_name' => 'Bistrović', 'email' => 'boris.bistrovic@example.com', 'is_admin' => false],
            ['first_name' => 'Vedran', 'last_name' => 'Žbulj', 'email' => 'vedran.zbulj@gmail.com', 'is_admin' => true],
        ];

        foreach ($players as $player) {
            $isAdmin = (bool) ($player['is_admin'] ?? false);
            unset($player['is_admin']);

            if (! $isAdmin) {
                $player['password'] = $seedUserPassword;
            }

            $user = User::factory()->create($player);

            $user->assignRole($playerRole);

            if ($isAdmin) {
                $user->assignRole($adminRole);
            }
        }
    }
}

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

        $players = [
            ['first_name' => 'Igor', 'last_name' => 'Levak', 'email' => 'igor.levak@example.com'],
            ['first_name' => 'Andrija', 'last_name' => 'Munđar', 'email' => 'andrija.mundar@example.com'],
            ['first_name' => 'Tomislav', 'last_name' => 'Pavlic', 'email' => 'tomislav.pavlic@example.com'],
            ['first_name' => 'Damjan', 'last_name' => 'Petričević', 'email' => 'damjan.petricevic@example.com'],
            ['first_name' => 'Predrag', 'last_name' => 'Rojko', 'email' => 'predrag.rojko@example.com'],
            ['first_name' => 'Dalibor', 'last_name' => 'Strišković', 'email' => 'dalibor.striskovic@example.com'],
            ['first_name' => 'Darko', 'last_name' => 'Terek', 'email' => 'darko.terek@example.com'],
            ['first_name' => 'Matija', 'last_name' => 'Terek', 'email' => 'matija.terek@example.com'],
            ['first_name' => 'Igor', 'last_name' => 'Vibović', 'email' => 'igor.vibovic@example.com'],
            ['first_name' => 'Ivan', 'last_name' => 'Zlatarek', 'email' => 'ivan.zlatarek@example.com'],
            ['first_name' => 'Krešimir', 'last_name' => 'Zvonarek', 'email' => 'kresimir.zvonarek@example.com'],
            ['first_name' => 'Vedran', 'last_name' => 'Žbulj', 'email' => 'vedran.zbulj@example.com'],
        ];

        foreach ($players as $player) {
            User::factory()->create($player)->assignRole($playerRole);
        }
    }
}

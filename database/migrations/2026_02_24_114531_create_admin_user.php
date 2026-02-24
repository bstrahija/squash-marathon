<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminFirstName = env('ADMIN_FIRST_NAME');
        $adminLastName = env('ADMIN_LAST_NAME');

        if (blank($adminEmail) || blank($adminPassword) || blank($adminFirstName) || blank($adminLastName)) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $playerRole = Role::firstOrCreate(['name' => RoleName::Player->value]);
        $adminRole = Role::firstOrCreate(['name' => RoleName::Admin->value]);

        $adminUser = User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'first_name' => $adminFirstName,
                'last_name' => $adminLastName,
                'password' => Hash::make($adminPassword),
            ]
        );

        $adminUser->assignRole([$playerRole, $adminRole]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $adminEmail = env('ADMIN_EMAIL');

        if (blank($adminEmail)) {
            return;
        }

        User::query()->where('email', $adminEmail)->delete();
    }
};

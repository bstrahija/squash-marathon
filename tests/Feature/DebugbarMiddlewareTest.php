<?php

use App\Enums\RoleName;
use App\Http\Middleware\EnableDebugbarForAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class FakeDebugbar
{
    public bool $enabled = false;

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}

test('debugbar middleware enables debugbar for admins', function () {
    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $debugbar = new FakeDebugbar;
    app()->instance('debugbar', $debugbar);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $admin);

    $response = (new EnableDebugbarForAdmin)->handle(
        $request,
        fn () => response('ok'),
    );

    expect($response->getStatusCode())->toBe(200);
    expect($debugbar->enabled)->toBeTrue();
});

test('debugbar middleware disables debugbar for non-admin users', function () {
    $user = User::factory()->create();

    $debugbar = new FakeDebugbar;
    $debugbar->enabled = true;
    app()->instance('debugbar', $debugbar);

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = (new EnableDebugbarForAdmin)->handle(
        $request,
        fn () => response('ok'),
    );

    expect($response->getStatusCode())->toBe(200);
    expect($debugbar->enabled)->toBeFalse();
});

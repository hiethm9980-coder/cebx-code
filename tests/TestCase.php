<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    protected function createAuthenticatedUser(string $role = 'super-admin')
    {
        $account = \App\Models\Account::factory()->create();
        $roleModel = \App\Models\Role::where('slug', $role)->first();

        return \App\Models\User::factory()->create([
            'account_id' => $account->id,
            'role_id' => $roleModel?->id,
        ]);
    }

    protected function authHeaders($user = null)
    {
        $user = $user ?? $this->createAuthenticatedUser();
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }
}

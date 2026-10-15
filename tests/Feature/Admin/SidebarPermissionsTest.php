<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeSidebarUser(array $permissions): User
{
    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'sidebar-'.Str::random(6), 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('sidebar hides links the user has no permission for', function () {
    $user = makeSidebarUser(['view-client']);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk()
        ->assertSee('Companies', false)
        ->assertSee('Clients', false)
        ->assertDontSee('Users', false)
        ->assertDontSee('Roles', false)
        ->assertDontSee('Xero', false)
        ->assertDontSee('Bulk Direct Debit Charges', false)
        ->assertDontSee('Activity Logs', false);
});

test('sidebar shows direct debit links with matching permissions', function () {
    $user = makeSidebarUser(['view-direct-debit', 'view-xero', 'view-payment']);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk()
        ->assertSee('Direct Debit Payments', false)
        ->assertSee('Bulk Direct Debit Charges', false)
        ->assertSee('Xero', false)
        ->assertSee('Stripe Payouts', false)
        ->assertDontSee('Users', false)
        ->assertDontSee('Companies', false);
});

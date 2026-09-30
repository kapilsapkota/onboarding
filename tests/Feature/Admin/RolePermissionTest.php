<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeRoleAdmin(array $permissions = ['view-role', 'create-role', 'edit-role', 'delete-role', 'view-permission', 'create-permission', 'edit-permission', 'delete-permission']): User
{
    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('roles index renders display names and grouped permissions', function () {
    $admin = makeRoleAdmin(['view-role']);
    Permission::firstOrCreate(['name' => 'view-user', 'guard_name' => 'web', 'group' => 'Users']);
    $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web', 'display_name' => 'Manager']);

    $this->actingAs($admin)->get('/admin/roles')
        ->assertOk()
        ->assertSee('Manager', false)
        ->assertSee('Display name', false)
        ->assertSee('Users', false);
});

test('authorized admin can create role with grouped permissions', function () {
    $admin = makeRoleAdmin();
    $p1 = Permission::firstOrCreate(['name' => 'view-client', 'guard_name' => 'web', 'group' => 'Clients']);
    $p2 = Permission::firstOrCreate(['name' => 'edit-client', 'guard_name' => 'web', 'group' => 'Clients']);

    $this->actingAs($admin)->post('/admin/roles', [
        'name' => 'team-lead',
        'display_name' => 'Team Lead',
        'permissions' => [$p1->id, $p2->id],
    ])->assertRedirect();

    $role = Role::where('name', 'team-lead')->firstOrFail();
    expect($role->display_name)->toBe('Team Lead')
        ->and($role->permissions->pluck('id')->sort()->values()->all())->toBe([$p1->id, $p2->id]);
});

test('role update syncs permissions and display name', function () {
    $admin = makeRoleAdmin();
    $keep = Permission::firstOrCreate(['name' => 'view-report', 'guard_name' => 'web', 'group' => 'Reports']);
    $drop = Permission::firstOrCreate(['name' => 'export-report', 'guard_name' => 'web', 'group' => 'Reports']);

    $role = Role::create(['name' => 'analyst', 'guard_name' => 'web']);
    $role->syncPermissions([$keep->id, $drop->id]);

    $this->actingAs($admin)->put("/admin/roles/{$role->id}", [
        'name' => 'analyst',
        'display_name' => 'Analyst',
        'permissions' => [$keep->id],
    ])->assertRedirect();

    $role->refresh();
    expect($role->display_name)->toBe('Analyst')
        ->and($role->permissions->pluck('id')->all())->toBe([$keep->id]);
});

test('role delete is blocked for super-admin and roles with users', function () {
    $admin = makeRoleAdmin();

    $superAdmin = Role::where('name', 'super-admin')->firstOrFail();
    $this->actingAs($admin)->delete("/admin/roles/{$superAdmin->id}")->assertSessionHasErrors('role');
    expect(Role::where('name', 'super-admin')->exists())->toBeTrue();

    $used = Role::create(['name' => 'used-role', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($used);
    $this->actingAs($admin)->delete("/admin/roles/{$used->id}")->assertSessionHasErrors('role');
    expect(Role::where('name', 'used-role')->exists())->toBeTrue();

    $free = Role::create(['name' => 'free-role', 'guard_name' => 'web']);
    $this->actingAs($admin)->delete("/admin/roles/{$free->id}")->assertRedirect();
    expect(Role::where('name', 'free-role')->exists())->toBeFalse();
});

test('super-admin role cannot be renamed', function () {
    $admin = makeRoleAdmin();
    $superAdmin = Role::where('name', 'super-admin')->firstOrFail();

    $this->actingAs($admin)->put("/admin/roles/{$superAdmin->id}", [
        'name' => 'renamed',
        'permissions' => [],
    ])->assertForbidden();
});

test('untrusted permission ids are rejected on role store', function () {
    $admin = makeRoleAdmin();

    $this->actingAs($admin)->post('/admin/roles', [
        'name' => 'bad-role',
        'permissions' => [999999],
    ])->assertSessionHasErrors('permissions.0');

    expect(Role::where('name', 'bad-role')->exists())->toBeFalse();
});

test('permissions index renders groups and permission crud works', function () {
    $admin = makeRoleAdmin(['view-permission', 'create-permission', 'edit-permission', 'delete-permission']);
    Permission::firstOrCreate(['name' => 'view-client', 'guard_name' => 'web', 'group' => 'Clients']);

    $this->actingAs($admin)->get('/admin/permissions')
        ->assertOk()
        ->assertSee('Clients', false)
        ->assertSee('Module group', false);

    $this->actingAs($admin)->post('/admin/permissions', [
        'name' => 'archive-client',
        'group' => 'Clients',
    ])->assertRedirect();

    $permission = Permission::where('name', 'archive-client')->firstOrFail();
    expect($permission->group)->toBe('Clients');

    $this->actingAs($admin)->put("/admin/permissions/{$permission->id}", [
        'name' => 'archive-client',
        'group' => 'Reports',
    ])->assertRedirect();
    expect($permission->refresh()->group)->toBe('Reports');

    $this->actingAs($admin)->delete("/admin/permissions/{$permission->id}")->assertRedirect();
    expect(Permission::where('name', 'archive-client')->exists())->toBeFalse();
});

test('guests and unauthorized users are blocked from role and permission routes', function () {
    $role = Role::create(['name' => 'some-role', 'guard_name' => 'web']);
    $permission = Permission::create(['name' => 'some-permission', 'guard_name' => 'web']);

    $this->get('/admin/roles')->assertRedirect('/login');
    $this->get('/admin/permissions')->assertRedirect('/login');

    $plain = User::factory()->create();

    $this->actingAs($plain)->get('/admin/roles')->assertForbidden();
    $this->actingAs($plain)->post('/admin/roles', ['name' => 'x'])->assertForbidden();
    $this->actingAs($plain)->put("/admin/roles/{$role->id}", ['name' => 'x'])->assertForbidden();
    $this->actingAs($plain)->delete("/admin/roles/{$role->id}")->assertForbidden();

    $this->actingAs($plain)->get('/admin/permissions')->assertForbidden();
    $this->actingAs($plain)->post('/admin/permissions', ['name' => 'x', 'group' => 'Y'])->assertForbidden();
    $this->actingAs($plain)->put("/admin/permissions/{$permission->id}", ['name' => 'x', 'group' => 'Y'])->assertForbidden();
    $this->actingAs($plain)->delete("/admin/permissions/{$permission->id}")->assertForbidden();
});

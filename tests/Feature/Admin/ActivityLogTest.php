<?php

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeActivityViewer(array $permissions = ['view-activity-log']): User
{
    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'activity-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('creating a model records an activity log with causer', function () {
    $user = makeActivityViewer(['view-activity-log']);
    $this->actingAs($user);

    $company = Company::create(['name' => 'Log Co', 'slug' => 'log-co']);

    $log = ActivityLog::where('subject_type', $company->getMorphClass())
        ->where('subject_id', $company->id)
        ->where('event', 'created')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($user->id)
        ->and($log->description)->toContain('Log Co');
});

test('updating a model records changes without secrets', function () {
    $user = makeActivityViewer(['view-activity-log']);
    $this->actingAs($user);

    $company = Company::create(['name' => 'Before Co', 'slug' => 'before-co']);
    $company->update(['name' => 'After Co']);

    $log = ActivityLog::where('subject_type', $company->getMorphClass())
        ->where('subject_id', $company->id)
        ->where('event', 'updated')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['changes']['name']['old'])->toBe('Before Co')
        ->and($log->properties['changes']['name']['new'])->toBe('After Co');
});

test('login records an auth activity log', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    expect(ActivityLog::where('event', 'login')->where('causer_id', $user->id)->exists())->toBeTrue();
});

test('activity log index requires permission', function () {
    $plain = User::factory()->create();

    $this->actingAs($plain)->get(route('admin.activity-logs.index'))->assertForbidden();

    $viewer = makeActivityViewer();
    $this->actingAs($viewer)->get(route('admin.activity-logs.index'))->assertOk()->assertSee('Activity Logs', false);
});

test('index defaults to timeline with avatars and inline diffs', function () {
    $user = makeActivityViewer(['view-activity-log']);
    $this->actingAs($user);

    $company = Company::create(['name' => 'Before Co', 'slug' => 'before-co']);
    $company->update(['name' => 'After Co']);

    $response = $this->actingAs($user)->get(route('admin.activity-logs.index'))->assertOk();

    $response->assertSee('Timeline', false)
        ->assertSee('Today', false)
        ->assertSee('Before Co', false)
        ->assertSee('After Co', false)
        ->assertSee('ago', false);
});

test('index rows open details in a modal without leaving the page', function () {
    $user = makeActivityViewer(['view-activity-log']);
    $this->actingAs($user);

    $company = Company::create(['name' => 'Modal Co', 'slug' => 'modal-co']);
    $company->update(['name' => 'Modal Co 2']);

    $response = $this->actingAs($user)->get(route('admin.activity-logs.index'))->assertOk();

    $response->assertSee('activity-log-modal', false)
        ->assertSee('openLog', false)
        ->assertSee('click any row for details', false);
});

test('index table view renders the classic table', function () {
    $viewer = makeActivityViewer();
    $this->actingAs($viewer)
        ->get(route('admin.activity-logs.index', ['view' => 'table']))
        ->assertOk()
        ->assertSee('Area / Event', false);
});

test('index falls back to timeline for unknown view modes', function () {
    $viewer = makeActivityViewer();
    $this->actingAs($viewer);

    Company::create(['name' => 'Fallback Co', 'slug' => 'fallback-co']);

    $this->actingAs($viewer)
        ->get(route('admin.activity-logs.index', ['view' => 'nope']))
        ->assertOk()
        ->assertSee('Today', false)
        ->assertDontSee('Area / Event', false);
});

test('mutating admin requests are logged without passwords', function () {
    Permission::firstOrCreate(['name' => 'view-user', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create-user', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'view-activity-log', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'activity-creator', 'guard_name' => 'web']);
    $role->syncPermissions(['view-user', 'create-user', 'view-activity-log']);
    $admin = User::factory()->create();
    $admin->assignRole($role);

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Logged Guy',
        'email' => 'logged@example.com',
        'password' => 'super-secret-123',
    ])->assertRedirect();

    $requestLog = ActivityLog::where('log_name', 'request')->latest()->first();

    expect($requestLog)->not->toBeNull()
        ->and(json_encode($requestLog->properties))->not->toContain('super-secret-123');
});

<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeAdmin(array $permissions = ['view-user', 'create-user', 'edit-user', 'delete-user']): User
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

function makeCompany(string $name): Company
{
    return Company::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(5),
    ]);
}

test('index renders avatar phone role companies columns', function () {
    $admin = makeAdmin(['view-user']);
    makeCompany('Render Co');

    $this->actingAs($admin)->get('/admin/users')
        ->assertOk()
        ->assertSee('Avatar', false)
        ->assertSee('Phone', false)
        ->assertSee('Companies', false)
        ->assertSee('avatarPreview', false);
});

test('authorized admin can create user with phone, role, companies and avatar', function () {
    Storage::fake('public');
    $admin = makeAdmin();
    Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    $c1 = makeCompany('Acme One');
    $c2 = makeCompany('Acme Two');

    $response = $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New Guy',
        'email' => 'newguy@example.com',
        'phone' => '0400 111 222',
        'password' => 'secret123',
        'role' => 'manager',
        'companies' => [$c1->id, $c2->id],
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 800, 600),
    ]);

    $response->assertRedirect();
    $user = User::where('email', 'newguy@example.com')->firstOrFail();

    expect($user->phone)->toBe('0400 111 222')
        ->and($user->hasRole('manager'))->toBeTrue()
        ->and($user->companies->pluck('id')->sort()->values()->all())->toBe(collect([$c1->id, $c2->id])->sort()->values()->all())
        ->and($user->avatar)->not->toBeNull()
        ->and($user->avatar)->toEndWith('.webp');

    Storage::disk('public')->assertExists($user->avatar);

    $path = Storage::disk('public')->path($user->avatar);
    [$width, $height] = getimagesize($path);
    expect($width)->toBe(400)->and($height)->toBe(400);
});

test('avatar is converted to webp even when png uploaded', function () {
    Storage::fake('public');
    $admin = makeAdmin();

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Png Guy',
        'email' => 'png@example.com',
        'password' => 'secret123',
        'avatar' => UploadedFile::fake()->image('avatar.png', 900, 500),
    ])->assertRedirect();

    $user = User::where('email', 'png@example.com')->firstOrFail();
    expect($user->avatar)->toEndWith('.webp');
    Storage::disk('public')->assertExists($user->avatar);
});

test('update replaces avatar and deletes old file, password optional', function () {
    Storage::fake('public');
    $admin = makeAdmin();
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $c1 = makeCompany('Old Co');
    $c2 = makeCompany('New Co');

    $user = User::factory()->create();
    $unused = $c1;

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Tmp',
        'email' => 'tmp-replace@example.com',
        'password' => 'secret123',
        'avatar' => UploadedFile::fake()->image('a.jpg', 800, 800),
    ]);

    $user = User::where('email', 'tmp-replace@example.com')->firstOrFail();
    $oldAvatar = $user->avatar;
    $oldHash = $user->password;
    Storage::disk('public')->assertExists($oldAvatar);

    $response = $this->actingAs($admin)->put("/admin/users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => $user->email,
        'phone' => '0411 222 333',
        'role' => 'user',
        'companies' => [$c2->id],
        'avatar' => UploadedFile::fake()->image('b.png', 1000, 800),
    ]);

    $response->assertRedirect();
    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->phone)->toBe('0411 222 333')
        ->and($user->password)->toBe($oldHash)
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->companies->pluck('id')->all())->toBe([$c2->id]);

    Storage::disk('public')->assertMissing($oldAvatar);
    Storage::disk('public')->assertExists($user->avatar);
});

test('delete removes avatar file', function () {
    Storage::fake('public');
    $admin = makeAdmin();

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Gone',
        'email' => 'gone@example.com',
        'password' => 'secret123',
        'avatar' => UploadedFile::fake()->image('a.jpg', 500, 500),
    ]);

    $user = User::where('email', 'gone@example.com')->firstOrFail();
    $avatar = $user->avatar;

    $this->actingAs($admin)->delete("/admin/users/{$user->id}")->assertRedirect();
    Storage::disk('public')->assertMissing($avatar);
    expect(User::where('email', 'gone@example.com')->exists())->toBeFalse();
});

test('guests and unauthorized users are blocked server-side', function () {
    $victim = User::factory()->create();

    $this->post('/admin/users', ['name' => 'x', 'email' => 'x@x.com', 'password' => 'secret123'])
        ->assertRedirect('/login');

    $plain = User::factory()->create();
    $this->actingAs($plain)->post('/admin/users', [
        'name' => 'x', 'email' => 'x2@x.com', 'password' => 'secret123',
    ])->assertForbidden();

    $this->actingAs($plain)->put("/admin/users/{$victim->id}", [
        'name' => 'hacked', 'email' => $victim->email,
    ])->assertForbidden();

    $this->actingAs($plain)->delete("/admin/users/{$victim->id}")->assertForbidden();
});

test('untrusted role and company ids are rejected', function () {
    Storage::fake('public');
    $admin = makeAdmin();

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Bad',
        'email' => 'bad@example.com',
        'password' => 'secret123',
        'role' => 'nonexistent-role',
        'companies' => [999999],
    ])->assertSessionHasErrors(['role', 'companies.0']);

    expect(User::where('email', 'bad@example.com')->exists())->toBeFalse();
});

test('non super-admin cannot assign super-admin role', function () {
    $admin = makeAdmin(['view-user', 'create-user', 'edit-user', 'delete-user']);
    $admin->removeRole('super-admin');
    $limited = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $limited->syncPermissions(['view-user', 'create-user', 'edit-user']);
    $admin->assignRole($limited);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Evil',
        'email' => 'evil@example.com',
        'password' => 'secret123',
        'role' => 'super-admin',
    ])->assertForbidden();
});

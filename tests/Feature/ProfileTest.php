<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('profile page shows the avatar upload with current photo', function () {
    $user = User::factory()->create(['avatar' => 'avatars/current.webp']);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk()
        ->assertSee('Profile photo', false)
        ->assertSee('avatars/current.webp', false);
});

test('user can upload a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('me.jpg', 800, 600),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/profile');

    $user->refresh();

    expect($user->avatar)->not->toBeNull()
        ->and($user->avatar)->toEndWith('.webp');

    Storage::disk('public')->assertExists($user->avatar);
});

test('uploading a new photo deletes the old one', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/old.webp', 'old');

    $user = User::factory()->create(['avatar' => 'avatars/old.webp']);

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('new.png', 900, 500),
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->avatar)->not->toBe('avatars/old.webp');

    Storage::disk('public')->assertMissing('avatars/old.webp');
});

test('user can remove their profile photo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/gone.webp', 'old');

    $user = User::factory()->create(['avatar' => 'avatars/gone.webp']);

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'remove_avatar' => '1',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->avatar)->toBeNull();

    Storage::disk('public')->assertMissing('avatars/gone.webp');
});

test('sidebar shows the user avatar photo', function () {
    $user = User::factory()->create(['avatar' => 'avatars/side.webp']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertSee('avatars/side.webp', false);
});

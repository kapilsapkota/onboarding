<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client as TwilioClient;

uses(RefreshDatabase::class);

function makeCoverageViewer(array $permissions = ['view-activity-log', 'view-user']): User
{
    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'coverage-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeFakeTwilioClient(bool $fail = false): TwilioClient
{
    return new class($fail) extends TwilioClient
    {
        public $messages;

        public function __construct(bool $fail)
        {
            $this->messages = new class($fail)
            {
                public function __construct(private bool $fail) {}

                public function create($to, $params): object
                {
                    if ($this->fail) {
                        throw new TwilioException('provider down');
                    }

                    return (object) ['sid' => 'SM123', 'status' => 'queued'];
                }
            };
        }
    };
}

test('every sent email is logged with recipient and subject', function () {
    Mail::to('customer@example.com')->send(
        (new Mailable)->subject('Quote Q-100 ready')->html('<p>Hello</p>')
    );

    $log = ActivityLog::where('log_name', 'mail')->where('event', 'sent')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['to'])->toContain('customer@example.com')
        ->and($log->properties['subject'])->toBe('Quote Q-100 ready')
        ->and($log->properties['html_body'])->toContain('Hello')
        ->and($log->description)->toContain('customer@example.com');
});

test('sent email body is available on demand and on the detail page', function () {
    Mail::to('reader@example.com')->send(
        (new Mailable)->subject('Body check')->html('<p>Whole body here</p>')
    );

    $log = ActivityLog::where('log_name', 'mail')->where('event', 'sent')->latest()->first();

    expect($log)->not->toBeNull();

    $plain = User::factory()->create();
    $this->actingAs($plain)->getJson(route('admin.activity-logs.body', $log))->assertForbidden();

    $viewer = makeCoverageViewer();
    $this->actingAs($viewer)->getJson(route('admin.activity-logs.body', $log))
        ->assertOk()
        ->assertJsonPath('html', '<p>Whole body here</p>')
        ->assertJsonPath('truncated', false);

    $this->actingAs($viewer)->get(route('admin.activity-logs.show', $log))
        ->assertOk()
        ->assertSee('Email preview', false)
        ->assertSee('Whole body here', false);
});

test('mail notifications are logged with recipient and class', function () {
    $user = User::factory()->create();

    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['mail'];
        }

        public function toMail($notifiable): MailMessage
        {
            return (new MailMessage)->subject('Payment received')->line('Thanks');
        }
    };

    $user->notify($notification);

    $log = ActivityLog::where('log_name', 'notification')->where('event', 'sent')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['channel'])->toBe('mail')
        ->and($log->properties['notifiable'])->toContain((string) $user->id)
        ->and($log->properties['notifiable'])->toContain($user->email);

    expect(ActivityLog::where('log_name', 'mail')->where('event', 'sent')->exists())->toBeTrue();
});

test('sms sends and failures are logged with recipient', function () {
    $service = new SmsService(makeFakeTwilioClient());
    $result = $service->send('0400 000 000', 'hello');

    expect($result->success)->toBeTrue();

    $sent = ActivityLog::where('log_name', 'sms')->where('event', 'sent')->latest()->first();

    expect($sent)->not->toBeNull()
        ->and($sent->properties['to'])->toBe('+61400000000')
        ->and($sent->properties['provider_sid'])->toBe('SM123');

    $failing = new SmsService(makeFakeTwilioClient(fail: true));
    $failed = $failing->send('0400 000 001', 'hello');

    expect($failed->success)->toBeFalse();
    expect(ActivityLog::where('log_name', 'sms')->where('event', 'failed')->exists())->toBeTrue();
});

test('admin page views are logged but the log viewer itself is not', function () {
    $viewer = makeCoverageViewer();

    $this->actingAs($viewer)->get('/admin/users')->assertOk();

    expect(
        ActivityLog::where('log_name', 'request')->where('event', 'view')->exists()
    )->toBeTrue();

    $this->actingAs($viewer)->get(route('admin.activity-logs.index'))->assertOk();

    expect(
        ActivityLog::where('log_name', 'request')
            ->whereJsonContains('properties->route', 'admin.activity-logs.index')
            ->exists()
    )->toBeFalse();
});

test('prune command deletes only entries outside the retention window', function () {
    $old = ActivityLog::create([
        'log_name' => 'request',
        'description' => 'old entry',
    ]);
    $old->created_at = now()->subDays(200);
    $old->save();

    $recent = ActivityLog::create([
        'log_name' => 'request',
        'description' => 'recent entry',
    ]);

    $this->artisan('activity:prune', ['--days' => 180])->assertSuccessful();

    expect(ActivityLog::find($old->id))->toBeNull()
        ->and(ActivityLog::find($recent->id))->not->toBeNull();
});

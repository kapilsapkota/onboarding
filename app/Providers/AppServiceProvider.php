<?php

namespace App\Providers;

use App\Services\EmailService;
use App\Services\SmsService;
use App\Support\Activity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmailService::class);
        $this->app->singleton(SmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Event::listen(Login::class, function (Login $event): void {
            Activity::record(
                description: "Logged in ({$event->user->email})",
                event: 'login',
                properties: ['email' => $event->user->email],
                logName: 'auth',
                causer: $event->user,
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                Activity::record(
                    description: "Logged out ({$event->user->email})",
                    event: 'logout',
                    properties: ['email' => $event->user->email],
                    logName: 'auth',
                    causer: $event->user,
                );
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            Activity::record(
                description: 'Failed login attempt',
                event: 'failed-login',
                properties: ['email' => is_string($event->credentials['email'] ?? null) ? $event->credentials['email'] : null],
                logName: 'auth',
            );
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            try {
                // $event->message resolves to the Symfony Email via __get.
                $email = $event->message;
                $addresses = fn (?array $list): array => collect($list ?? [])
                    ->map(fn ($address) => method_exists($address, 'getAddress') ? $address->getAddress() : (string) $address)
                    ->values()->all();

                $to = $addresses($email->getTo());
                $subject = $email->getSubject() ?? '(no subject)';

                // Whole-body capture so the log can show the exact email sent.
                // Capped to keep the activity table lean; fetched on demand.
                $cap = fn (?string $body): array => $body === null
                    ? [null, false]
                    : (mb_strlen($body) > 150000
                        ? [mb_substr($body, 0, 150000), true]
                        : [$body, false]);

                [$htmlBody, $htmlTruncated] = $cap(
                    method_exists($email, 'getHtmlBody') ? $email->getHtmlBody() : null
                );
                [$textBody, $textTruncated] = $cap(
                    method_exists($email, 'getTextBody') ? $email->getTextBody() : null
                );

                $attachments = [];

                foreach (method_exists($email, 'getAttachments') ? $email->getAttachments() : [] as $attachment) {
                    try {
                        $attachments[] = method_exists($attachment, 'getFilename')
                            ? ($attachment->getFilename() ?? 'attachment')
                            : 'attachment';
                    } catch (\Throwable) {
                        $attachments[] = 'attachment';
                    }
                }

                Activity::record(
                    description: 'Email "'.$subject.'" sent to '.implode(', ', $to),
                    event: 'sent',
                    properties: array_filter([
                        'to' => $to,
                        'cc' => $addresses($email->getCc()),
                        'bcc' => $addresses($email->getBcc()),
                        'subject' => $subject,
                        'message_id' => $event->sent->getMessageId(),
                        'html_body' => $htmlBody,
                        'text_body' => $textBody,
                        'body_truncated' => ($htmlTruncated || $textTruncated) ? true : null,
                        'attachments' => $attachments,
                    ]),
                    logName: 'mail',
                );
            } catch (\Throwable $e) {
                // Logging must never break mail sending.
            }
        });

        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            try {
                Activity::record(
                    description: class_basename($event->notification).' notification sent via '.$event->channel.' to '.static::notifiableLabel($event->notifiable),
                    event: 'sent',
                    properties: [
                        'notification' => $event->notification::class,
                        'channel' => $event->channel,
                        'notifiable' => static::notifiableLabel($event->notifiable),
                    ],
                    logName: 'notification',
                );
            } catch (\Throwable $e) {
                // Logging must never break notifications.
            }
        });

        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            try {
                Activity::record(
                    description: class_basename($event->notification).' notification failed via '.$event->channel.' to '.static::notifiableLabel($event->notifiable),
                    event: 'failed',
                    properties: [
                        'notification' => $event->notification::class,
                        'channel' => $event->channel,
                        'notifiable' => static::notifiableLabel($event->notifiable),
                        'error' => is_string($event->data['error'] ?? null) ? $event->data['error'] : null,
                    ],
                    logName: 'notification',
                );
            } catch (\Throwable $e) {
                // Logging must never break notifications.
            }
        });
    }

    private static function notifiableLabel(mixed $notifiable): string
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return 'anonymous ('.collect($notifiable->routes)->map(fn ($route, $channel) => $channel.': '.(is_array($route) ? implode(',', $route) : $route))->implode('; ').')';
        }

        if (is_object($notifiable) && method_exists($notifiable, 'getKey')) {
            $email = isset($notifiable->email) ? ' <'.$notifiable->email.'>' : '';

            return class_basename($notifiable).' #'.$notifiable->getKey().$email;
        }

        return is_object($notifiable) ? $notifiable::class : (string) $notifiable;
    }
}

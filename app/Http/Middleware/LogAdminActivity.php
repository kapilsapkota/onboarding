<?php

namespace App\Http\Middleware;

use App\Support\Activity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    /**
     * @var list<string>
     */
    private array $sensitive = [
        'password', 'password_confirmation', 'current_password',
        'secret_key', 'webhook_secret', 'secret', 'token',
        'access_token', 'refresh_token', 'publishable_key',
        'signature_data',
    ];

    /**
     * Paths that are never logged (own log viewer, debug tooling, livewire polling).
     *
     * @var list<string>
     */
    private array $excludedPrefixes = [
        'admin/activity-logs',
        'livewire',
        'log-viewer',
        'pulse',
        '_ignition',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        try {
            if (! $request->user()) {
                return $response;
            }

            $path = trim($request->path(), '/');

            foreach ($this->excludedPrefixes as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    return $response;
                }
            }

            $isMutating = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
            $isAdminArea = str_starts_with($path, 'admin');

            if (! $isMutating && ! $isAdminArea) {
                return $response;
            }

            if ($response->isServerError()) {
                return $response;
            }

            $route = $request->route();
            $action = $route ? $route->getName() ?? $route->getActionName() : $path;

            Activity::record(
                description: "{$request->method()} {$action}",
                event: $request->isMethod('get') ? 'view' : strtolower($request->method()),
                properties: [
                    'route' => $action,
                    'status' => $response->getStatusCode(),
                    'input' => $this->filteredInput($request),
                ],
                logName: 'request',
            );
        } catch (\Throwable $e) {
            // Logging must never break the request.
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function filteredInput(Request $request): array
    {
        $input = $request->except($this->sensitive);

        return collect($input)
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : '['.gettype($value).']')
            ->toArray();
    }
}

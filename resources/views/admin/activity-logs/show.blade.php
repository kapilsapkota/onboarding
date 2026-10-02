<x-app-layout>
    @section('title', 'Activity Detail')

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Activity Detail
                </h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $log->created_at->format('d M Y H:i:s') }} ({{ $log->created_at->diffForHumans() }})
                </p>
            </div>
            <a href="{{ route('admin.activity-logs.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                Back to logs
            </a>
        </div>
    </x-slot>

    <x-message />

    <div class="mx-auto max-w-4xl space-y-5 px-4 pb-10 sm:px-6 lg:px-10">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Description</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $log->description }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Who</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">
                        {{ $log->causer_name }}
                        @if ($log->causer?->email)
                            <span class="text-gray-400">({{ $log->causer->email }})</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Area / Event</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $log->log_name }} / {{ $log->event ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Subject</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">
                        @if ($log->subject_type)
                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Request</dt>
                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $log->method ?? '—' }} {{ $log->url ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">IP / Agent</dt>
                    <dd class="mt-1 break-all text-gray-900 dark:text-gray-100">{{ $log->ip ?? '—' }}</dd>
                    <dd class="mt-1 break-all text-xs text-gray-400">{{ $log->user_agent }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Change details</h3>
            @if ($log->properties)
                <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <p class="mt-3 text-sm text-gray-500">No extra details recorded.</p>
            @endif
        </div>
    </div>
</x-app-layout>

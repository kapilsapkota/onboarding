<x-app-layout>
    @section('title', 'Activity Detail')

    @php
        $theme = $log->eventTheme();
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    @if ($log->avatarUrl())
                        <img src="{{ $log->avatarUrl() }}" alt="{{ $log->causer_name }}"
                             class="h-11 w-11 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700" />
                    @else
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full text-sm font-bold ring-2 ring-gray-100 dark:ring-gray-700 {{ $log->avatarColor() }}">
                            {{ $log->initials() }}
                        </span>
                    @endif
                    <span class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full text-white ring-2 ring-white dark:ring-gray-900 {{ $theme['dot'] }}">
                        <span class="text-[9px] font-bold uppercase">{{ substr((string) ($log->event ?? $log->log_name), 0, 1) }}</span>
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        {{ $log->description }}
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $log->causer_name }} · <span title="{{ $log->created_at->format('d M Y H:i:s') }}">{{ $log->created_at->diffForHumans() }}</span> · {{ $log->created_at->format('d M Y H:i:s') }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.activity-logs.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                Back to logs
            </a>
        </div>
    </x-slot>

    <x-message />

    <div class="mx-auto max-w-4xl space-y-5 px-4 pb-10 sm:px-6 lg:px-10">
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $log->log_name }}</span>
            @if ($log->event)
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $theme['badge'] }}">{{ $log->event }}</span>
            @endif
            @if ($log->subject_type)
                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                </span>
            @endif
        </div>

        @if ($log->log_name === 'mail' && (! empty($log->properties['html_body']) || ! empty($log->properties['text_body'])))
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Email preview</h3>
                @if (! empty($log->properties['html_body']))
                    <iframe srcdoc="{{ $log->properties['html_body'] }}" sandbox="" title="Email preview" class="mt-3 h-[500px] w-full rounded-xl border border-gray-200 bg-white dark:border-gray-700"></iframe>
                @else
                    <pre class="mt-3 max-h-[500px] overflow-y-auto whitespace-pre-wrap rounded-xl bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $log->properties['text_body'] }}</pre>
                @endif
                @if (! empty($log->properties['body_truncated']))
                    <p class="mt-2 text-[11px] text-amber-600 dark:text-amber-400">Body was over the storage cap and has been trimmed.</p>
                @endif
                @if (! empty($log->properties['attachments']))
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-semibold text-gray-400">Attachments:</span>
                        @foreach ($log->properties['attachments'] as $file)
                            <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $file }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Description</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $log->description }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Who</dt>
                    <dd class="mt-1 flex items-center gap-2 text-gray-900 dark:text-gray-100">
                        @if ($log->avatarUrl())
                            <img src="{{ $log->avatarUrl() }}" alt="{{ $log->causer_name }}"
                                 class="h-6 w-6 rounded-full object-cover" />
                        @else
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold {{ $log->avatarColor() }}">
                                {{ $log->initials() }}
                            </span>
                        @endif
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
            @if (! empty($log->properties['changes']) && is_array($log->properties['changes']))
                <div class="mt-3 space-y-1.5 rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                    @foreach ($log->properties['changes'] as $field => $change)
                        <div class="flex flex-col gap-1 text-xs sm:flex-row sm:items-center">
                            <span class="w-32 shrink-0 truncate font-semibold text-gray-500 dark:text-gray-400">
                                {{ str_replace('_', ' ', (string) $field) }}
                            </span>
                            <span class="min-w-0 flex-1 truncate rounded bg-red-50 px-2 py-1 text-red-700 line-through decoration-red-300 dark:bg-red-900/20 dark:text-red-300">
                                {{ $log->previewValue(is_array($change) ? ($change['old'] ?? null) : null) }}
                            </span>
                            <svg class="h-3 w-3 shrink-0 rotate-90 text-gray-400 sm:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"/>
                            </svg>
                            <span class="min-w-0 flex-1 truncate rounded bg-emerald-50 px-2 py-1 font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                {{ $log->previewValue(is_array($change) ? ($change['new'] ?? null) : $change) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                @if ($log->properties)
                    <details class="mt-4">
                        <summary class="cursor-pointer text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View raw data</summary>
                        <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            @elseif ($log->properties)
                <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <p class="mt-3 text-sm text-gray-500">No extra details recorded.</p>
            @endif
        </div>
    </div>
</x-app-layout>

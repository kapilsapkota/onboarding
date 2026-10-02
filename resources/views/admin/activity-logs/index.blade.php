<x-app-layout>
    @section('title', 'Activity Logs')

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        Activity Logs
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Detail logs of all activities in the system.
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <x-message />

    <div class="space-y-5 pb-10" x-data="activityFeed()" @keydown.escape.window="close()">
        <section class="mb-5">
    <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        Activity logs
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Search and filter activity
                    </p>
                </div>

                @if(request()->hasAny(['search', 'log_name', 'event', 'causer_id', 'date_from', 'date_to']))
                    <a href="{{ route('admin.activity-logs.index', ['view' => $viewMode]) }}"
                       class="text-xs font-medium text-gray-500 transition hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400">
                        Clear all
                    </a>
                @endif
            </div>

            <form
                method="GET"
                action="{{ route('admin.activity-logs.index') }}"
                id="activityFilters"
                class="p-4"
            >
                <input type="hidden" name="view" value="{{ $viewMode }}" />

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-12">

                    {{-- Search --}}
                    <div class="lg:col-span-4">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            Search
                        </label>

                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-4 w-4 text-gray-400"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>

                            <input
                                type="search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search description, subject, IP..."
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3 text-sm text-gray-900 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder:text-gray-500 dark:focus:bg-gray-700"
                            />
                        </div>
                    </div>

                    {{-- Area --}}
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            Area
                        </label>

                        <select
                            name="log_name"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-100 dark:focus:bg-gray-700"
                        >
                            <option value="">All areas</option>

                            @foreach ($logNames as $name)
                                <option value="{{ $name }}"
                                    @selected(request('log_name') === $name)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Event --}}
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            Event
                        </label>

                        <select
                            name="event"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-100 dark:focus:bg-gray-700"
                        >
                            <option value="">All events</option>

                            @foreach ($events as $event)
                                <option value="{{ $event }}"
                                    @selected(request('event') === $event)>
                                    {{ $event }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- User --}}
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            User
                        </label>

                        <select
                            name="causer_id"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-100 dark:focus:bg-gray-700"
                        >
                            <option value="">All users</option>

                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                    @selected((string) request('causer_id') === (string) $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            From
                        </label>

                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-100 dark:focus:bg-gray-700"
                        />
                    </div>

                    {{-- Date To --}}
                    <div class="lg:col-span-1">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            To
                        </label>

                        <input
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-500/10 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-100 dark:focus:bg-gray-700"
                        />
                    </div>

                </div>
            </form>
        </div>
    </div>
</section>

{{-- Submit search automatically when Enter is pressed --}}
<script>
    document.getElementById('activityFilters')?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target.name === 'search') {
            event.preventDefault();
            this.submit();
        }
    });
</script>


        {{-- View toggle + count --}}
        <section>
            <div class="mx-auto flex max-w-full flex-wrap items-center justify-between gap-3 px-4 sm:px-6 lg:px-10">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $logs->total() }} {{ \Illuminate\Support\Str::plural('activity', $logs->total()) }}
                    @if(request()->hasAny(['search', 'log_name', 'event', 'causer_id', 'date_from', 'date_to']))
                        for these filters
                    @endif
                    · click any row for details
                </p>

                <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-gray-700" role="tablist" aria-label="Activity view">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'timeline']) }}"
                       role="tab"
                       aria-selected="{{ $viewMode === 'timeline' ? 'true' : 'false' }}"
                       class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $viewMode === 'timeline' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Timeline
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}"
                       role="tab"
                       aria-selected="{{ $viewMode === 'table' ? 'true' : 'false' }}"
                       class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $viewMode === 'table' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                        </svg>
                        Table
                    </a>
                </div>
            </div>
        </section>

        @if ($viewMode === 'timeline')
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                @php
                    $grouped = $logs->getCollection()->groupBy(fn ($log) => $log->created_at->format('Y-m-d'));
                @endphp

                @forelse ($grouped as $day => $dayLogs)
                    @php
                        $dayCarbon = $dayLogs->first()->created_at;
                        $dayLabel = $dayCarbon->isToday()
                            ? 'Today'
                            : ($dayCarbon->isYesterday() ? 'Yesterday' : $dayCarbon->format('l, d M Y'));
                    @endphp

                    <div class="mb-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50/70 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-900/40">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ $dayLabel }}
                            </h3>
                            <span class="rounded-full bg-gray-200/70 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $dayLogs->count() }}
                            </span>
                            <span class="ml-auto text-[11px] text-gray-400">
                                {{ $dayCarbon->format('d M Y') }}
                            </span>
                        </div>

                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($dayLogs as $log)
                                @php
                                    $theme = $log->eventTheme();
                                    $firstChange = $log->changesList(1)[0] ?? null;
                                @endphp
                                <li>
                                    <button
                                        type="button"
                                        @click="openLog({{ $log->id }})"
                                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 dark:hover:bg-gray-700/40"
                                    >
                                        {{-- Avatar, vertically centred with the row --}}
                                        <span class="relative shrink-0 self-center">
                                            @if ($log->avatarUrl())
                                                <img src="{{ $log->avatarUrl() }}" alt="{{ $log->causer_name }}"
                                                     class="h-9 w-9 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-600" />
                                            @else
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full text-[11px] font-bold {{ $log->avatarColor() }}">
                                                    {{ $log->initials() }}
                                                </span>
                                            @endif
                                            <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-gray-800 {{ $theme['dot'] }}"></span>
                                        </span>

                                        {{-- Text --}}
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm text-gray-900 dark:text-gray-100">
                                                <span class="font-semibold">{{ $log->causer_name }}</span>
                                                <span class="font-normal text-gray-500 dark:text-gray-400"> {{ $log->description }}</span>
                                            </span>
                                            <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-400">
                                                @if ($log->event)
                                                    <span class="inline-flex rounded-full px-1.5 py-px text-[11px] font-medium {{ $theme['badge'] }}">{{ $log->event }}</span>
                                                @endif
                                                @if ($log->subject_type)
                                                    <span>{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</span>
                                                @endif
                                                @if ($firstChange)
                                                    <span class="inline-flex min-w-0 items-center gap-1">
                                                        <span class="font-medium text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $firstChange['field']) }}:</span>
                                                        <span class="truncate line-through">{{ $log->previewValue($firstChange['old']) }}</span>
                                                        <span>→</span>
                                                        <span class="truncate font-medium text-gray-600 dark:text-gray-300">{{ $log->previewValue($firstChange['new']) }}</span>
                                                    </span>
                                                @endif
                                                @if ($log->ip)
                                                    <span>· {{ $log->ip }}</span>
                                                @endif
                                            </span>
                                        </span>

                                        {{-- Time + chevron --}}
                                        <span class="flex shrink-0 items-center gap-1 self-center">
                                            <span class="text-right">
                                                <span class="block whitespace-nowrap text-xs text-gray-400" title="{{ $log->created_at->format('d M Y H:i:s') }}">{{ $log->created_at->diffForHumans() }}</span>
                                                <span class="block whitespace-nowrap text-[11px] text-gray-300 dark:text-gray-500">{{ $log->created_at->format('H:i') }}</span>
                                            </span>
                                            <svg class="h-4 w-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-14 text-center dark:border-gray-600 dark:bg-gray-800">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">No activity yet</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Try adjusting your search or filters.</p>
                    </div>
                @endforelse

                @if ($logs->hasPages())
                    <div class="mt-6">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </section>
        @else
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">When</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Who</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Activity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Area / Event</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">IP</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($logs as $log)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                            <span title="{{ $log->created_at->format('d M Y H:i:s') }}">{{ $log->created_at->diffForHumans() }}</span>
                                            <div class="text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                @if ($log->avatarUrl())
                                                    <img src="{{ $log->avatarUrl() }}" alt="{{ $log->causer_name }}"
                                                         class="h-8 w-8 rounded-full object-cover" />
                                                @else
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-[11px] font-bold {{ $log->avatarColor() }}">
                                                        {{ $log->initials() }}
                                                    </span>
                                                @endif
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $log->causer_name }}</div>
                                                    @if ($log->causer?->email)
                                                        <div class="text-xs text-gray-400">{{ $log->causer->email }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $log->description }}</div>
                                            @if ($log->subject_type)
                                                <div class="text-xs text-gray-400">
                                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $log->log_name }}</span>
                                            @if ($log->event)
                                                <span class="ml-1 inline-flex rounded-full px-2 py-0.5 text-xs {{ $log->eventTheme()['badge'] }}">{{ $log->event }}</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">{{ $log->ip }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                @click="openLog({{ $log->id }})"
                                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                            >View</button>
                                            @can('delete-activity-log')
                                                <form method="POST" action="{{ route('admin.activity-logs.destroy', $log) }}"
                                                      class="ml-2 inline" onsubmit="return confirm('Delete this log entry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                            No activity found for these filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($logs->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- Instant detail modal (no page navigation) --}}
        <div
            id="activity-log-modal"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-label="Activity details"
        >
            <div class="fixed inset-0 bg-gray-900/60" @click="close()"></div>

            <div class="relative mx-auto my-6 w-full max-w-2xl px-4 sm:my-10">
                <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800" @click.stop>
                    <template x-if="active">
                        <div>
                            {{-- Header --}}
                            <div class="flex items-center gap-3 border-b border-gray-100 p-5 dark:border-gray-700">
                                <span class="relative shrink-0 self-center">
                                    <template x-if="active.avatar">
                                        <img :src="active.avatar" :alt="active.causer" class="h-11 w-11 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-600" />
                                    </template>
                                    <template x-if="! active.avatar">
                                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full text-sm font-bold" :class="active.avatarColor" x-text="active.initials"></span>
                                    </template>
                                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2 ring-white dark:ring-gray-800" :class="active.eventDot"></span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="active.description"></p>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        <span x-text="active.causer"></span>
                                        <span> · </span>
                                        <span x-text="active.timeHuman"></span>
                                        <span> · </span>
                                        <span x-text="active.timeFull"></span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="close()"
                                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                                    aria-label="Close details"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Body --}}
                            <div class="max-h-[60vh] space-y-4 overflow-y-auto p-5">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300" x-text="active.logName"></span>
                                    <template x-if="active.event">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="active.eventBadge" x-text="active.event"></span>
                                    </template>
                                    <template x-if="active.subject">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300" x-text="active.subject"></span>
                                    </template>
                                </div>

                                <template x-if="active.changes.length">
                                    <div>
                                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">What changed</h4>
                                        <div class="space-y-1.5 rounded-xl bg-gray-50 p-3 dark:bg-gray-900/50">
                                            <template x-for="change in active.changes" :key="change.field">
                                                <div class="flex flex-col gap-1 text-xs sm:flex-row sm:items-center">
                                                    <span class="w-32 shrink-0 truncate font-semibold text-gray-500 dark:text-gray-400" x-text="change.field"></span>
                                                    <span class="min-w-0 flex-1 truncate rounded bg-red-50 px-2 py-1 text-red-700 line-through decoration-red-300 dark:bg-red-900/20 dark:text-red-300" x-text="change.old"></span>
                                                    <svg class="h-3 w-3 shrink-0 rotate-90 text-gray-400 sm:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"/>
                                                    </svg>
                                                    <span class="min-w-0 flex-1 truncate rounded bg-emerald-50 px-2 py-1 font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300" x-text="change.new"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="active.isMail">
                                    <div>
                                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">Email preview</h4>
                                        <template x-if="active.bodyLoading">
                                            <div class="flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-6 text-xs text-gray-400 dark:bg-gray-900/50">
                                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                                </svg>
                                                Loading email…
                                            </div>
                                        </template>
                                        <template x-if="! active.bodyLoading && active.emailHtml">
                                            <iframe :srcdoc="active.emailHtml" sandbox="" title="Email preview" class="h-96 w-full rounded-xl border border-gray-200 bg-white dark:border-gray-700"></iframe>
                                        </template>
                                        <template x-if="! active.bodyLoading && ! active.emailHtml && active.emailText">
                                            <pre class="max-h-96 overflow-y-auto whitespace-pre-wrap rounded-xl bg-gray-50 p-3 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300" x-text="active.emailText"></pre>
                                        </template>
                                        <template x-if="! active.bodyLoading && ! active.emailHtml && ! active.emailText">
                                            <p class="rounded-xl bg-gray-50 px-3 py-4 text-xs text-gray-400 dark:bg-gray-900/50">No body was captured for this email (sent before body logging was enabled).</p>
                                        </template>
                                        <template x-if="active.bodyTruncated">
                                            <p class="mt-1.5 text-[11px] text-amber-600 dark:text-amber-400">Body was over the storage cap and has been trimmed.</p>
                                        </template>
                                        <template x-if="active.attachments.length">
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                <span class="text-[11px] font-semibold text-gray-400">Attachments:</span>
                                                <template x-for="file in active.attachments" :key="file">
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.589-6.589a4 4 0 00-5.657-5.657l-6.588 6.589a6 6 0 108.485 8.485L20 11"/></svg>
                                                        <span x-text="file"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="active.method || active.ip">
                                    <div>
                                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">Request</h4>
                                        <dl class="grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                            <template x-if="active.method">
                                                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-900/50">
                                                    <dt class="font-semibold text-gray-400">Method / URL</dt>
                                                    <dd class="mt-0.5 break-all text-gray-700 dark:text-gray-300" x-text="active.method + ' ' + (active.url || '')"></dd>
                                                </div>
                                            </template>
                                            <template x-if="active.ip">
                                                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-900/50">
                                                    <dt class="font-semibold text-gray-400">IP</dt>
                                                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300" x-text="active.ip"></dd>
                                                </div>
                                            </template>
                                        </dl>
                                        <template x-if="active.agent">
                                            <p class="mt-2 break-all text-[11px] text-gray-400" x-text="active.agent"></p>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="active.raw">
                                    <details>
                                        <summary class="cursor-pointer text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View raw data</summary>
                                        <pre class="mt-2 overflow-x-auto rounded-xl bg-gray-50 p-3 text-[11px] text-gray-600 dark:bg-gray-900 dark:text-gray-300" x-text="active.raw"></pre>
                                    </details>
                                </template>
                            </div>

                            {{-- Footer --}}
                            <div class="flex items-center justify-between gap-3 border-t border-gray-100 p-4 dark:border-gray-700">
                                <a :href="active.showUrl" class="text-xs font-semibold text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Open full page</a>
                                <div class="flex items-center gap-2">
                                    @can('delete-activity-log')
                                        <form method="POST" :action="active.deleteUrl" onsubmit="return confirm('Delete this log entry?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:hover:bg-red-900/20">Delete</button>
                                        </form>
                                    @endcan
                                    <button
                                        type="button"
                                        @click="close()"
                                        class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600"
                                    >Close</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <script>
            function activityFeed() {
                return {
                    open: false,
                    active: null,
                    items: {
                        @foreach ($logs as $log)
                            @php
                                $theme = $log->eventTheme();
                                $modalChanges = [];
                                foreach (($log->properties['changes'] ?? []) as $field => $change) {
                                    $old = is_array($change) ? ($change['old'] ?? null) : null;
                                    $new = is_array($change) ? ($change['new'] ?? null) : $change;
                                    $modalChanges[] = [
                                        'field' => str_replace('_', ' ', (string) $field),
                                        'old' => $log->previewValue($old),
                                        'new' => $log->previewValue($new),
                                    ];
                                }
                                $hasBody = ! empty($log->properties['html_body'] ?? null) || ! empty($log->properties['text_body'] ?? null);
                                $modalPayload = [
                                    'id' => $log->id,
                                    'description' => $log->description,
                                    'isMail' => $log->log_name === 'mail',
                                    'hasBody' => $hasBody,
                                    'bodyUrl' => $hasBody ? route('admin.activity-logs.body', $log) : null,
                                    'emailHtml' => null,
                                    'emailText' => null,
                                    'bodyTruncated' => false,
                                    'attachments' => [],
                                    'bodyLoading' => false,
                                    'bodyLoaded' => false,
                                    'causer' => $log->causer_name,
                                    'avatar' => $log->avatarUrl(),
                                    'initials' => $log->initials(),
                                    'avatarColor' => $log->avatarColor(),
                                    'event' => $log->event,
                                    'eventDot' => $theme['dot'],
                                    'eventBadge' => $theme['badge'],
                                    'logName' => $log->log_name,
                                    'subject' => $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : null,
                                    'timeFull' => $log->created_at->format('d M Y H:i:s'),
                                    'timeHuman' => $log->created_at->diffForHumans(),
                                    'method' => $log->method,
                                    'url' => $log->url,
                                    'ip' => $log->ip,
                                    'agent' => $log->user_agent,
                                    'changes' => $modalChanges,
                                    'raw' => $log->properties ? json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null,
                                    'showUrl' => route('admin.activity-logs.show', $log),
                                    'deleteUrl' => route('admin.activity-logs.destroy', $log),
                                ];
                            @endphp
                            {{ $log->id }}: @json($modalPayload),
                        @endforeach
                    },
                    openLog(id) {
                        this.active = this.items[id] ?? null;

                        if (! this.active) {
                            return;
                        }

                        this.open = true;
                        document.documentElement.classList.add('overflow-hidden');

                        if (this.active.isMail && this.active.hasBody && ! this.active.bodyLoaded && ! this.active.bodyLoading) {
                            this.active.bodyLoading = true;

                            fetch(this.active.bodyUrl, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                                .then((response) => (response.ok ? response.json() : null))
                                .then((data) => {
                                    if (! data) {
                                        return;
                                    }

                                    this.active.emailHtml = data.html;
                                    this.active.emailText = data.text;
                                    this.active.bodyTruncated = !! data.truncated;
                                    this.active.attachments = data.attachments || [];
                                })
                                .finally(() => {
                                    this.active.bodyLoading = false;
                                    this.active.bodyLoaded = true;
                                });
                        }
                    },
                    close() {
                        this.open = false;
                        this.active = null;
                        document.documentElement.classList.remove('overflow-hidden');
                    },
                };
            }
        </script>
    </div>
</x-app-layout>

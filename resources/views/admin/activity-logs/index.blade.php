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

    <div class="space-y-5 pb-10">
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
                    <a href="{{ route('admin.activity-logs.index') }}"
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
                                            {{ $log->created_at->format('d M Y H:i:s') }}
                                            <div class="text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $log->causer_name }}</div>
                                            @if ($log->causer?->email)
                                                <div class="text-xs text-gray-400">{{ $log->causer->email }}</div>
                                            @endif
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
                                                <span class="ml-1 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $log->event }}</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">{{ $log->ip }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a href="{{ route('admin.activity-logs.show', $log) }}"
                                               class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View</a>
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
    </div>
</x-app-layout>

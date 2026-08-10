@php
    $attempts = $delivery->attempts->groupBy('type')->map(
        fn ($group) => $group->sortByDesc('attempt_number')->first()
    );

    $overallColor = match($delivery->status) {
        'completed'        => 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20',
        'partially_failed' => 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20',
        'failed'           => 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20',
        'cancelled'        => 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800',
        default            => 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20',
    };

    $overallDot = match($delivery->status) {
        'completed'        => 'bg-green-500',
        'partially_failed' => 'bg-yellow-500',
        'failed'           => 'bg-red-500',
        'cancelled'        => 'bg-gray-400',
        default            => 'bg-blue-500',
    };

    $overallText = match($delivery->status) {
        'completed'        => 'text-green-700 dark:text-green-400',
        'partially_failed' => 'text-yellow-700 dark:text-yellow-400',
        'failed'           => 'text-red-700 dark:text-red-400',
        'cancelled'        => 'text-gray-600 dark:text-gray-400',
        default            => 'text-blue-700 dark:text-blue-400',
    };

    $isActive = in_array($delivery->status, ['pending', 'processing']);

    $typeLabels = [
        'generate_pdf'        => 'Generate PDF',
        'generate_public_url' => 'Generate Public Link',
        'sharepoint_upload'   => 'SharePoint Upload',
        'email'               => 'Send Email',
        'sms'                 => 'Send SMS',
    ];

$typeOrder = ['generate_pdf', 'generate_public_url', 'sharepoint_upload', 'email', 'sms'];
$presentTypes = array_filter($typeOrder, fn ($t) => isset($attempts[$t]));
@endphp

{{-- Overall status --}}
<div class="mb-4 rounded-xl border {{ $overallColor }} p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="h-2.5 w-2.5 rounded-full {{ $overallDot }}
                        {{ $isActive ? 'animate-pulse' : '' }}"></div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Overall Status</p>
                <p class="text-sm font-semibold {{ $overallText }}">{{ $delivery->status_label }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-4 text-xs text-gray-400 dark:text-gray-500">
            @if($delivery->started_at)
                <span>
                    Started
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ $delivery->started_at->format('d M H:i') }}
                    </span>
                </span>
            @endif
            @if($delivery->completed_at)
                <span>
                    Completed
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ $delivery->completed_at->format('d M H:i') }}
                    </span>
                </span>
            @endif
        </div>
    </div>
</div>

{{-- Operation rows --}}
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white
            dark:border-gray-700 dark:bg-gray-800">

    @foreach($presentTypes as $i => $type)
        @php
            $attempt = $attempts[$type];
            $status  = $attempt->status;
            $isLast  = $i === count($presentTypes) - 1;

            $statusLabel = match($status) {
                'succeeded'  => 'Completed',
                'failed'     => 'Failed',
                'skipped'    => 'Skipped',
                'processing' => 'Processing...',
                default      => 'Pending',
            };

            $statusTextColor = match($status) {
                'succeeded'  => 'text-green-600 dark:text-green-400',
                'failed'     => 'text-red-600 dark:text-red-400',
                'skipped'    => 'text-gray-400 dark:text-gray-500',
                'processing' => 'text-blue-600 dark:text-blue-400',
                default      => 'text-gray-400 dark:text-gray-500',
            };

            $iconBg = match($status) {
                'succeeded'  => 'bg-green-100 dark:bg-green-900/30',
                'failed'     => 'bg-red-100 dark:bg-red-900/30',
                'processing' => 'bg-blue-100 dark:bg-blue-900/30',
                default      => 'bg-gray-100 dark:bg-gray-700',
            };

            $iconColor = match($status) {
                'succeeded'  => 'text-green-600 dark:text-green-400',
                'failed'     => 'text-red-600 dark:text-red-400',
                'processing' => 'text-blue-600 dark:text-blue-400 animate-spin',
                default      => 'text-gray-400 dark:text-gray-500',
            };
        @endphp

        <div @class([
            'px-5 py-4',
            'border-b border-gray-100 dark:border-gray-700' => !$isLast,
        ])>
            <div class="flex items-start gap-4">

                {{-- Status icon --}}
                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center
                            rounded-lg {{ $iconBg }}">
                    <svg class="h-4 w-4 {{ $iconColor }}" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        @if($status === 'succeeded')
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M5 13l4 4L19 7"/>
                        @elseif($status === 'failed')
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        @elseif($status === 'processing')
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        @else
                            <circle cx="12" cy="12" r="4" fill="currentColor"/>
                        @endif
                    </svg>
                </div>

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-start justify-between gap-2">

                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $typeLabels[$type] ?? ucwords(str_replace('_', ' ', $type)) }}
                            </span>
                            @if($attempt->attempt_number > 1)
                                <span class="inline-flex items-center rounded-full bg-gray-100
                                             px-2 py-0.5 text-xs text-gray-600
                                             dark:bg-gray-700 dark:text-gray-400">
                                    Attempt {{ $attempt->attempt_number }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold {{ $statusTextColor }}">
                                {{ $statusLabel }}
                            </span>

                            @if($attempt->isRetryable())
                                <button
                                    type="button"
                                    class="ds-retry-btn inline-flex items-center gap-1.5 rounded-lg
                                           border border-red-200 bg-red-50 px-3 py-1
                                           text-xs font-semibold text-red-700 transition
                                           hover:bg-red-100 disabled:cursor-not-allowed
                                           disabled:opacity-60 dark:border-red-800
                                           dark:bg-red-900/20 dark:text-red-400
                                           dark:hover:bg-red-900/30"
                                    data-retry-attempt="{{ $attempt->id }}"
                                    data-delivery-id="{{ $delivery->id }}"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582
                                                 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0
                                                 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Retry
                                </button>
                            @endif
                        </div>

                    </div>

                    {{-- Timestamps --}}
                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs
                                text-gray-400 dark:text-gray-500">
                        @if($attempt->started_at)
                            <span>Started {{ $attempt->started_at->format('d M H:i:s') }}</span>
                        @endif
                        @if($attempt->completed_at)
                            <span class="text-green-600 dark:text-green-500">
                                Completed {{ $attempt->completed_at->format('d M H:i:s') }}
                            </span>
                        @endif
                        @if($attempt->failed_at)
                            <span class="text-red-500 dark:text-red-400">
                                Failed {{ $attempt->failed_at->format('d M H:i:s') }}
                            </span>
                        @endif
                    </div>

                    {{-- Error message --}}
                    @if($attempt->error_message && in_array($status, ['failed', 'skipped']))
                        <div @class([
                            'mt-2.5 rounded-lg px-3 py-2 text-xs leading-relaxed',
                            'border border-red-100 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-400'
                                => $status === 'failed',
                            'border border-gray-100 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400'
                                => $status === 'skipped',
                        ])>
                            {{ $attempt->error_message }}
                        </div>
                    @endif

                </div>

            </div>
        </div>

    @endforeach

</div>

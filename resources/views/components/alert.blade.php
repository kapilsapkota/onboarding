@props([
    'type'       => null,
    'connection' => null,
])

@php
    $hasSessionError      = session('error');
    $hasSessionSuccess    = session('success');
    $hasValidationErrors  = $errors->any();
    $hasXeroReauth        = session('xero_reauth_required') || $connection?->needs_reauth;
    $hasXeroInactive      = $connection && ! $connection->is_active && ! $connection->needs_reauth;

    $hasAnything = $hasSessionError || $hasSessionSuccess || $hasValidationErrors
                || $hasXeroReauth  || $hasXeroInactive;
@endphp

@if ($hasAnything)

    <div x-data="{ open: true }" x-show="open" x-transition class="mb-4 space-y-3">

        {{-- ── Xero re-auth (highest severity, own block) ──────────────────── --}}
        @if ($hasXeroReauth)
            <div class="p-4 rounded border border-yellow-300 bg-yellow-50 text-yellow-800 relative">
                <button type="button" @click="open = false" class="absolute top-2 right-2 hover:opacity-70">✕</button>
                <p class="font-medium">Xero reconnection required</p>
                @if ($connection?->reauth_reason)
                    <p class="text-sm mt-0.5 text-yellow-700">{{ $connection->reauth_reason }}</p>
                @endif
                <p class="text-sm mt-1">
                    Your Xero authorization has expired or been revoked. Syncing is paused until you reconnect.
                </p>
                <a href="{{ route('admin.xero.connect') }}"
                   class="inline-block mt-2 px-3 py-1.5 text-sm rounded bg-yellow-600 text-white hover:bg-yellow-700">
                    Reconnect to Xero
                </a>
            </div>
        @endif

        {{-- ── Xero inactive (soft disconnect, lower severity) ─────────────── --}}
        @if ($hasXeroInactive)
            <div class="p-4 rounded border border-blue-200 bg-blue-50 text-blue-700 relative">
                <button type="button" @click="open = false" class="absolute top-2 right-2 hover:opacity-70">✕</button>
                <p class="text-sm">
                    The Xero connection is currently inactive.
                    <a href="{{ route('admin.xero.connect') }}" class="underline font-medium">Reconnect</a>
                    to resume syncing.
                </p>
            </div>
        @endif

        {{-- ── Standard flash / validation ─────────────────────────────────── --}}
        @if ($hasSessionError || $hasSessionSuccess || $hasValidationErrors)
            <div class="p-4 rounded relative
                @if ($hasSessionError || $hasValidationErrors)
                    bg-red-50 border border-red-200 text-red-700
                @else
                    bg-green-50 border border-green-200 text-green-700
                @endif
            ">
                <button type="button" @click="open = false" class="absolute top-2 right-2 hover:opacity-70">✕</button>

                @if ($hasSessionError)
                    <p>{{ session('error') }}</p>
                @endif

                @if ($hasSessionSuccess)
                    <p>{{ session('success') }}</p>
                @endif

                @if ($hasValidationErrors)
                    <ul class="text-sm list-disc list-inside mt-2 pr-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

    </div>

@endif

@props(['type' => null])

@php
    $hasSessionError = session('error');
    $hasSessionSuccess = session('success');
    $hasValidationErrors = $errors->any();
@endphp

@if ($hasSessionError || $hasSessionSuccess || $hasValidationErrors)

    <div
        x-data="{ open: true }"
        x-show="open"
        x-transition
        class="mb-4 p-4 rounded relative
        @if($hasSessionError || $hasValidationErrors)
            bg-red-50 border border-red-200 text-red-700
        @elseif($hasSessionSuccess)
            bg-green-50 border border-green-200 text-green-700
        @endif
        "
    >

        <!-- Close Button -->
        <button
            type="button"
            @click="open = false"
            class="absolute top-2 right-2 hover:opacity-70"
        >
            ✕
        </button>

        <!-- Session Error -->
        @if ($hasSessionError)
            <p>{{ session('error') }}</p>
        @endif

        <!-- Session Success -->
        @if ($hasSessionSuccess)
            <p>{{ session('success') }}</p>
        @endif

        <!-- Validation Errors -->
        @if ($hasValidationErrors)
            <ul class="text-sm list-disc list-inside mt-2 pr-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

    </div>

@endif

<div class="flex justify-between pt-4 pb-2">
    @if($back)
        <button type="button" onclick="prevStep()" class="btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </button>
    @else
        <div></div>
    @endif

    <button type="button" onclick="{{ $next }}" class="btn-primary">
        {{ $nextLabel }}
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
</div>

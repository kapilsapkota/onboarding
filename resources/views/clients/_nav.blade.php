<div class="flex justify-between mt-6 pt-5 border-t border-gray-100 dark:border-gray-700">
    @if($back)
        <button type="button" onclick="prevStep()"
                class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm font-medium px-5 py-2.5 rounded-lg shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </button>
    @else
        <div></div>
    @endif

    <button type="button" onclick="{{ $next }}"
            class="inline-flex items-center gap-2 text-white text-sm font-bold px-5 py-2.5 rounded-lg shadow-sm transition-all"
            style="background:var(--wld-gold)"
            onmouseover="this.style.background='var(--wld-gold-hover)'"
            onmouseout="this.style.background='var(--wld-gold)'">
        {{ $nextLabel }}
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
</div>

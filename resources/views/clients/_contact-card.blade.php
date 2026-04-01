<div class="contact-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" data-index="{{ $index }}">

    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="8" r="4" stroke-width="2"/>
                <path stroke-linecap="round" stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Contact {{ $index + 1 }}</span>
            @if($primary)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                      style="background:var(--wld-gold-light);color:var(--wld-gold)">Primary</span>
            @endif
        </div>
        @if(!$primary)
            <button type="button" class="remove-btn inline-flex items-center gap-1 text-xs text-gray-400 hover:text-red-500 font-medium transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Remove
            </button>
        @endif
    </div>

    <div class="p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Full Name</label>
                <input type="text" name="contacts[{{ $index }}][full_name]" placeholder="Jane Smith"
                       class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 placeholder-gray-300 dark:placeholder-gray-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Role / Title</label>
                <input type="text" name="contacts[{{ $index }}][role]" placeholder="Finance Manager"
                       class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 placeholder-gray-300 dark:placeholder-gray-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Email Address</label>
                <input type="email" name="contacts[{{ $index }}][email]" placeholder="jane@company.com"
                       class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 placeholder-gray-300 dark:placeholder-gray-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Phone Number</label>
                <input type="tel" name="contacts[{{ $index }}][phone]" placeholder="+61 4xx xxx xxx"
                       class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 placeholder-gray-300 dark:placeholder-gray-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">WhatsApp</label>
                <input type="tel" name="contacts[{{ $index }}][whatsapp]" placeholder="+61 4xx xxx xxx"
                       class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 placeholder-gray-300 dark:placeholder-gray-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">LinkedIn URL</label>
                <input type="url" name="contacts[{{ $index }}][linkedin_url]" placeholder="https://linkedin.com/in/…"
                       class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 placeholder-gray-300 dark:placeholder-gray-500 transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Contact Type</label>
                <select name="contacts[{{ $index }}][contact_type]"
                        class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 appearance-none transition">
                    <option value="">Select type…</option>
                    @foreach(['Decision Maker','Finance','Marketing','Operations','Technical','Other'] as $t)
                        <option>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                    Birthday <span class="normal-case font-normal text-gray-400">(optional)</span>
                </label>
                <input type="date" name="contacts[{{ $index }}][birthday]"
                       class="wld-input w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3.5 py-2.5 transition">
            </div>
        </div>

        {{-- Consent --}}
        <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Marketing Consent</p>
            <div class="space-y-2">
                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition">
                    <input type="hidden" name="contacts[{{ $index }}][email_opt_in]" value="0">
                    <input type="checkbox" name="contacts[{{ $index }}][email_opt_in]" value="1"
                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 focus:ring-0"
                           style="accent-color:var(--wld-gold)">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Email Marketing</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Consents to receive marketing emails from {{ config('app.name') }}</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition">
                    <input type="hidden" name="contacts[{{ $index }}][sms_opt_in]" value="0">
                    <input type="checkbox" name="contacts[{{ $index }}][sms_opt_in]" value="1"
                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 focus:ring-0"
                           style="accent-color:var(--wld-gold)">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">SMS Marketing</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Consents to receive SMS messages from {{ config('app.name') }}</p>
                    </div>
                </label>
            </div>
        </div>
    </div>
</div>

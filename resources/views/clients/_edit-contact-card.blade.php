<div class="contact-card p-5 {{ $isPrimary ? '' : 'border-t border-gray-100 dark:border-gray-700' }}" data-index="{{ $idx }}">

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="8" r="4" stroke-width="2"/>
                <path stroke-linecap="round" stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Contact {{ $idx + 1 }}</span>
            @if($isPrimary)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                      style="background:#FBF6E9;color:#C9A84C">Primary</span>
            @endif
        </div>
        @if(!$isPrimary)
            <button type="button" class="remove-contact-btn inline-flex items-center gap-1 text-xs text-gray-400 hover:text-red-500 font-medium transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Remove
            </button>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach([
            ['full_name',    'Full Name',    'text',  'Jane Smith',             $contact->full_name    ?? ''],
            ['role',         'Role / Title', 'text',  'Finance Manager',        $contact->role         ?? ''],
            ['email',        'Email',        'email', 'jane@company.com',       $contact->email        ?? ''],
            ['phone',        'Phone',        'tel',   '+61 4xx xxx xxx',        $contact->phone        ?? ''],
            ['whatsapp',     'WhatsApp',     'tel',   '+61 4xx xxx xxx',        $contact->whatsapp     ?? ''],
            ['linkedin_url', 'LinkedIn URL', 'url',   'https://linkedin.com/in/…', $contact->linkedin_url ?? ''],
        ] as [$fname, $flabel, $ftype, $fph, $fval])
            <div>
                <label class="{{ $labelCls }}">{{ $flabel }}</label>
                <input type="{{ $ftype }}"
                       name="contacts[{{ $idx }}][{{ $fname }}]"
                       value="{{ old("contacts.{$idx}.{$fname}", $fval) }}"
                       placeholder="{{ $fph }}"
                       class="{{ $inputCls }}">
            </div>
        @endforeach

        <div>
            <label class="{{ $labelCls }}">Contact Type</label>
            <select name="contacts[{{ $idx }}][contact_type]" class="{{ $inputCls }} appearance-none">
                <option value="">Select type…</option>
                @foreach(['Decision Maker','Finance','Marketing','Operations','Technical','Other'] as $t)
                    <option {{ old("contacts.{$idx}.contact_type", $contact->contact_type ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $labelCls }}">Birthday <span class="normal-case font-normal text-gray-400">(optional)</span></label>
            <input type="date"
                   name="contacts[{{ $idx }}][birthday]"
                   value="{{ old("contacts.{$idx}.birthday", isset($contact->birthday) ? \Carbon\Carbon::parse($contact->birthday)->format('Y-m-d') : '') }}"
                   class="{{ $inputCls }}">
        </div>
    </div>

    {{-- Consent --}}
    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Marketing Consent</p>
        <div class="flex flex-wrap gap-3">
            <label class="flex items-center gap-2.5 p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition flex-1 min-w-[200px]">
                <input type="hidden" name="contacts[{{ $idx }}][email_opt_in]" value="0">
                <input type="checkbox" name="contacts[{{ $idx }}][email_opt_in]" value="1"
                       {{ old("contacts.{$idx}.email_opt_in", $contact->email_opt_in ?? false) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 dark:border-gray-600" style="accent-color:#C9A84C">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Email Marketing</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Consents to marketing emails</p>
                </div>
            </label>
            <label class="flex items-center gap-2.5 p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition flex-1 min-w-[200px]">
                <input type="hidden" name="contacts[{{ $idx }}][sms_opt_in]" value="0">
                <input type="checkbox" name="contacts[{{ $idx }}][sms_opt_in]" value="1"
                       {{ old("contacts.{$idx}.sms_opt_in", $contact->sms_opt_in ?? false) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-gray-300 dark:border-gray-600" style="accent-color:#C9A84C">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">SMS Marketing</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Consents to SMS messages</p>
                </div>
            </label>
        </div>
    </div>

</div>

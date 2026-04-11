<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Company Onboarding — {{ config('app.name', 'AIIT') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }

        :root {
            --gold:       #C9A84C;
            --gold-hover: #B8963D;
            --gold-light: #FBF6E9;
            --gold-ring:  rgba(201,168,76,0.2);
        }

        .wld-input {
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            color: #111827;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            padding: 0.625rem 0.875rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            appearance: none;
        }

        .wld-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-ring);
        }
        select.wld-input {
            font-size: 1rem; /* Must be explicitly 16px on iOS */
        }

        .wld-input::placeholder { color: #9ca3af; }

        .wld-input-group:focus-within {
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px var(--gold-ring);
        }

        .step-pane { display: none; }
        .step-pane.active { display: block; animation: fadeUp 0.2s ease both; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Step sidebar */
        .step-nav-item.is-active  { background: var(--gold-light); }
        .step-bubble-active       { background: var(--gold) !important; color: #fff !important; }
        .step-bubble-done         { background: #d1fae5 !important; color: #065f46 !important; }
        .step-label-active        { color: #111827 !important; font-weight: 600; }

        /* Same/copy toggle */
        .same-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--gold-light);
            border: 1px solid #fde68a;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #92400e;
            user-select: none;
            transition: background 0.15s;
        }

        .same-toggle input[type="checkbox"] {
            accent-color: var(--gold);
            width: 1rem;
            height: 1rem;
        }

        /* Employee row */
        .employee-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 0.75rem;
            align-items: end;
        }

        @media (max-width: 640px) {
            .employee-row { grid-template-columns: 1fr; }
        }

        /* Nav buttons */
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem; font-weight: 500;
            color: #6b7280;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-ghost:hover { background: #f9fafb; color: #374151; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.625rem 1.5rem;
            background: var(--gold);
            border: none;
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem; font-weight: 700;
            color: #fff;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-primary:hover { background: var(--gold-hover); }

        .btn-outline-sm {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.4rem 0.75rem;
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 0.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem; font-weight: 500;
            color: #9ca3af;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-outline-sm:hover { border-color: var(--gold); color: var(--gold); }

        .btn-remove {
            width: 2rem; height: 2rem;
            background: none;
            border: 1px solid #fee2e2;
            border-radius: 0.375rem;
            color: #fca5a5;
            cursor: pointer; transition: all 0.15s;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .btn-remove:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }

        /* Card section header */
        .card-head {
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
            background: #f9fafb;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-head h3 { font-size: 0.875rem; font-weight: 600; color: #1f2937; }

        /* Label */
        .field-label {
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #111;
            margin-bottom: 0.375rem;
        }

        /* Review summary */
        .review-row {
            display: grid;
            grid-template-columns: 9rem 1fr;
            gap: 0.75rem;
            padding: 0.625rem 1.25rem;
            border-bottom: 1px solid #f9fafb;
            font-size: 0.8125rem;
        }
        .review-row:last-child { border-bottom: none; }
        .review-key { color: #9ca3af; }
        .review-val { color: #1f2937; }
        .review-empty { color: #d1d5db; font-style: italic; }

        /* Progress */
        #progress-fill { background: var(--gold); transition: width 0.4s ease; }
        /* Prevent layout shift when keyboard opens */
        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        /* Fix iOS input zoom and scroll behaviour */
        input, select, textarea {
            font-size: 1rem !important;
            touch-action: manipulation;
        }
        @media (max-width: 1024px) {
            aside {
                position: relative !important;
                height: auto !important;
            }
        }
    </style>
</head>
<body class="antialiased bg-gray-50 min-h-screen">

<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- ── Sidebar ── --}}
    <aside class="w-full lg:w-64 xl:w-72 bg-white border-b lg:border-b-0 lg:border-r border-gray-200 flex-shrink-0">
        <div class="p-6 lg:p-8 lg:sticky lg:top-0 lg:h-screen lg:flex lg:flex-col">

            {{-- Brand text only --}}
            <div class="mb-8 lg:mb-10">
                <h1 class="text-lg font-bold text-black mt-1 leading-tight">Company Onboarding</h1>
            </div>

            {{-- Step list --}}
            <nav class="hidden lg:flex flex-col gap-0.5" id="steps-sidebar">
                @foreach([
                    ['Company Information', 'ABN, website, addresses'],
                    ['Main Contact',        'Primary point of contact'],
                    ['Accounts Payable',    'Finance contact details'],
                    ['Tech Contact',        'Technical point of contact'],
                    ['Services',            'Services required'],
                    ['Employees',           'Staff list & bulk upload'],
                ] as $i => $step)
                    <div class="step-nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 {{ $i === 0 ? 'is-active' : '' }}"
                         data-step="{{ $i + 1 }}">
                        <div class="step-bubble w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 bg-gray-100 text-black transition-all duration-150 {{ $i === 0 ? 'step-bubble-active' : '' }}">
                            <span class="step-num">{{ $i + 1 }}</span>
                            <svg class="step-check hidden w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="step-label text-sm truncate text-black {{ $i === 0 ? 'step-label-active' : '' }}">{{ $step[0] }}</p>
                            <p class="text-xs text-black truncate mt-0.5">{{ $step[1] }}</p>
                        </div>
                    </div>
                    @if($i < 4)
                        <div class="w-px h-2.5 bg-gray-200 ml-6"></div>
                    @endif
                @endforeach
            </nav>

            <div class="hidden lg:block mt-auto pt-6 border-t border-gray-100">
                <p class="text-xs text-black leading-relaxed">Nothing is mandatory except company name — submit with whatever you have and we'll follow up on the rest.</p>
            </div>
        </div>
    </aside>

    {{-- ── Main ── --}}
    <main class="flex-1 py-8 px-4 sm:px-8 lg:px-12 pb-16">
        <div class="max-w-7xl mx-auto">

            {{-- Progress --}}
            <div class="mb-8">
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-xs font-bold uppercase tracking-widest" id="progress-label"
                          style="color:var(--gold)">Step 1 of 5</span>
                    <span class="text-xs text-black" id="progress-pct">20%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                    <div id="progress-fill" class="h-1.5 rounded-full" style="width:20%"></div>
                </div>

                @if ($errors->any())
                    <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>

            <form action="{{ route('onboarding.store') }}" method="POST"
                  enctype="multipart/form-data"
                  id="onboarding-form"
                  novalidate>
                @csrf
                {{-- ══════════════════════════════════════
                     STEP 1 — Company Information
                ══════════════════════════════════════ --}}
                <div class="step-pane active" id="pane-1">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-black tracking-tight">Company Information</h2>
                        <p class="text-sm text-gray-500 mt-1">Start with the core details about your business.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100 mb-4">

                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div>
                                <label class="field-label">Company Name <span class="text-red-600">*</span></label>
                                <input type="text" name="company_name" value="{{ old('company_name') }}"
                                       placeholder="e.g. Acme Pty Ltd" autofocus class="wld-input">
                            </div>

                            <div>
                                <label class="field-label">Main Phone</label>
                                <input type="text" name="company_phone" value="{{ old('company_phone') }}"
                                       placeholder="e.g. 1300..." class="wld-input">
                            </div>

                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">ABN/ACN</label>
                                    <input type="text" name="abn" value="{{ old('abn') }}"
                                           placeholder="12 345 678 901" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Industry</label>
                                    <select name="industry" class="wld-input">
                                        <option value="">Select…</option>
                                        @foreach(['Hospitality & Food','Retail','Real Estate','Health & Wellness','Professional Services','Construction & Trades','Education','E-commerce','Events & Entertainment','Other'] as $ind)
                                            <option {{ old('industry') == $ind ? 'selected' : '' }}>{{ $ind }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <p class="text-xs font-bold text-black uppercase tracking-widest">Bank Details for Direct Debits</p>
{{--                            <div class="p-4 border-l-4 border-orange-50 bg-orange-50 text-sm text-black">--}}
{{--                                <p>--}}
{{--                                    By providing your bank details, you agree to the Direct Debit Request and the Direct Debit Request--}}
{{--                                    service agreement. You authorize <strong>Stripe Payments Australia Pty Ltd</strong> ACN 160 180 343--}}
{{--                                    (Direct Debit User ID number 507156) to debit your account through the Bulk Electronic Clearing--}}
{{--                                    System (BECS) on behalf of <strong>All in IT Solutions</strong> (the “Merchant”) for any amounts--}}
{{--                                    separately communicated to you by the Merchant.--}}
{{--                                </p>--}}
{{--                                <p>--}}
{{--                                    You certify that you are either an account holder or an authorized signatory on the account listed--}}
{{--                                    below. For more details, please refer to the--}}
{{--                                    <a href="https://stripe.com/au/legal/becs-dd-service-agreement"--}}
{{--                                       class="text-blue-600 hover:underline" target="_blank">--}}
{{--                                        BECS Direct Debit Service Agreement--}}
{{--                                    </a>.--}}
{{--                                </p>--}}
{{--                            </div>--}}
                            <!-- Account Information -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols- gap-4">
                                <div>
                                    <label class="field-label">Bank Name</label>
                                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="Bank Name" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Branch</label>
                                    <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" placeholder="Add if known." class="wld-input">
                                </div>
                            </div>

                            <!-- BSB and Bank Name -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="field-label">Account Name</label>
                                    <input type="text" name="account_name" value="{{ old('account_name') }}"
                                           placeholder="Account Name" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">BSB</label>
                                    <input type="text" name="bsb" value="{{ old('bsb') }}" placeholder="BSB" class="wld-input">
                                </div>

                                <div>
                                    <label class="field-label">Account Number</label>
                                    <input type="text" name="account_number" value="{{ old('account_number') }}"
                                           placeholder="Account Number" class="wld-input">
                                </div>

                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <p class="text-xs font-bold text-black uppercase tracking-widest">
                                Service Provider(s)
                            </p>

                            @php
                                $providers = \App\Models\Company::select('name','logo')->get();
                            @endphp

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                                @foreach($providers as $index => $provider)
                                    <label class="flex items-center gap-3 border border-gray-200 rounded-md px-3 py-2 hover:bg-gray-50 cursor-pointer transition-all duration-300 ease-in-out transform hover:scale-105">
                                        <!-- Logo Image -->
                                        <img src="{{ asset('images/'.$provider->logo) }}" alt="{{ $provider->name }} Logo" class="h-8 w-12 object-contain">

                                        <!-- Checkbox and Provider Name -->
                                        <div>
                                            <input type="checkbox"
                                                   name="service_providers[]"
                                                   value="{{ $provider->name }}"
                                                   class="accent-black"
                                                {{ collect(old('service_providers'))->contains($provider->name) ? 'checked' : '' }}>
                                            <span class="text-sm text-gray-800 ml-2">{{ $provider->name }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <p class="text-xs font-bold text-black uppercase tracking-widest">Website(s)</p>
                            <div id="websites-container" class="space-y-3">
                                <div class="flex gap-2 website-row">
                                    <input type="url" name="websites[]" value="{{ old('websites.0') }}"
                                           placeholder="https://yourwebsite.com" class="wld-input flex-1">
                                    <button type="button" class="btn-remove remove-website hidden" title="Remove">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <button type="button" id="add-website-btn" class="btn-outline-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                                Add another website
                            </button>
                        </div>

                        <div class="p-5 space-y-4">
                            <p class="text-xs font-bold text-black uppercase tracking-widest">Address</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols- gap-4">
                                <div>
                                    <label class="field-label">Address Line 1</label>
                                    <input type="text" name="address" value="{{ old('address') }}"
                                           placeholder="Unit / Street" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Address Line 2</label>
                                    <input type="text" name="address_second" value="{{ old('address_second') }}"
                                           placeholder="Unit / Street" class="wld-input">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label class="field-label">City / Suburb</label>
                                    <input type="text" name="city" value="{{ old('city') }}" placeholder="Sydney" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">State</label>
                                    <select name="state" class="wld-input">
                                        <option value="">Select State</option>
                                        <option value="NSW" {{ old('state') == 'NSW' ? 'selected' : '' }}>New South Wales</option>
                                        <option value="VIC" {{ old('state') == 'VIC' ? 'selected' : '' }}>Victoria</option>
                                        <option value="QLD" {{ old('state') == 'QLD' ? 'selected' : '' }}>Queensland</option>
                                        <option value="WA" {{ old('state') == 'WA' ? 'selected' : '' }}>Western Australia</option>
                                        <option value="SA" {{ old('state') == 'SA' ? 'selected' : '' }}>South Australia</option>
                                        <option value="TAS" {{ old('state') == 'TAS' ? 'selected' : '' }}>Tasmania</option>
                                        <option value="ACT" {{ old('state') == 'ACT' ? 'selected' : '' }}>Australian Capital Territory</option>
                                        <option value="NT" {{ old('state') == 'NT' ? 'selected' : '' }}>Northern Territory</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">Post Code</label>
                                    <input type="text" name="post_code" value="{{ old('post_code') }}" placeholder="2000" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Country</label>
                                    <input type="text" name="country" value="{{ old('country') }}" placeholder="Australia" class="wld-input">
                                </div>
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <p class="text-xs font-bold text-black uppercase tracking-widest">Social Profiles</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach([
                                    ['instagram','Instagram','@'],
                                    ['facebook', 'Facebook', 'fb/'],
                                    ['tiktok',   'TikTok',   '@'],
                                    ['linkedin', 'LinkedIn',  'in/'],
                                    ['twitter',  'X / Twitter','@'],
                                ] as [$sname,$slabel,$sprefix])
                                    <div>
                                        <label class="field-label">{{ $slabel }}</label>
                                        <div class="flex rounded-lg border border-gray-200 overflow-hidden wld-input-group transition">
                                            <span class="inline-flex items-center px-2.5 bg-gray-50 text-black text-xs border-r border-gray-200 flex-shrink-0 font-mono">{{ $sprefix }}</span>
                                            <input type="text" name="{{ $sname }}" value="{{ old($sname) }}"
                                                   placeholder="handle"
                                                   class="wld-input">
                                        </div>
                                    </div>
                                @endforeach
                                <div>
                                    <label class="field-label">WhatsApp Group Link</label>
                                    <input type="url" name="whatsapp_group" value="{{ old('whatsapp_group') }}"
                                           placeholder="https://chat.whatsapp.com/…" class="wld-input">
                                </div>
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <div>
                                <label class="field-label">Comments / Notes</label>
                                <textarea name="notes" rows="3" placeholder="Anything else we should know…"
                                          class="wld-input resize-none">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                    </div>

                    @include('clients._onboarding-nav', ['step' => 1, 'totalSteps' => 5, 'back' => false, 'next' => 'nextStep()', 'nextLabel' => 'Continue'])
                </div>

                {{-- ══════════════════════════════════════
                     STEP 2 — Main Contact
                ══════════════════════════════════════ --}}
                <div class="step-pane" id="pane-2">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-black tracking-tight">Main Contact</h2>
                        <p class="text-sm text-gray-500 mt-1">The primary person we'll be communicating with.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Full Name</label>
                                    <input type="text" name="contacts[0][full_name]" id="main_name"
                                           placeholder="Jane Smith" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Role / Title</label>
                                    <input type="text" name="contacts[0][role]"
                                           placeholder="Marketing Manager" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Email Address</label>
                                    <input type="email" name="contacts[0][email]" id="main_email"
                                           placeholder="jane@company.com" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Mobile</label>
                                    <input type="tel" name="contacts[0][phone]" id="main_phone"
                                           placeholder="+61 4xx xxx xxx" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">LinkedIn URL</label>
                                    <input type="url" name="contacts[0][linkedin_url]"
                                           placeholder="https://linkedin.com/in/…" class="wld-input">
                                </div>
                            </div>

                            <input type="hidden" name="contacts[0][contact_type]" value="Main Contact">
                            <input type="hidden" name="contacts[0][is_primary]" value="1">

                        </div>
                    </div>

                    @include('clients._onboarding-nav', ['step' => 2, 'totalSteps' => 5, 'back' => true, 'next' => 'nextStep()', 'nextLabel' => 'Continue'])
                </div>

                {{-- ══════════════════════════════════════
                     STEP 3 — Accounts Payable
                ══════════════════════════════════════ --}}
                <div class="step-pane" id="pane-3">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-black tracking-tight">Accounts Payable Contact</h2>
                        <p class="text-sm text-gray-500 mt-1">Who handles invoices and payments?</p>
                    </div>

                    {{-- Same as main toggle --}}
                    <label class="same-toggle mb-4 block" id="ap-same-label">
                        <input type="checkbox" id="ap_same_as_main" onchange="toggleSameAs('ap', this.checked)">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Same as Main Contact — copy details across
                    </label>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4" id="ap-fields">
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Full Name</label>
                                    <input type="text" name="contacts[1][full_name]" id="ap_name"
                                           placeholder="Alex Johnson" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Role / Title</label>
                                    <input type="text" name="contacts[1][role]"
                                           value="Accounts Payable" placeholder="Accounts Payable" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Email Address</label>
                                    <input type="email" name="contacts[1][email]" id="ap_email"
                                           placeholder="accounts@company.com" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Mobile</label>
                                    <input type="tel" name="contacts[1][phone]" id="ap_phone"
                                           placeholder="+61 4xx xxx xxx" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">LinkedIn URL</label>
                                    <input type="url" name="contacts[1][linkedin_url]"
                                           placeholder="https://linkedin.com/in/…" class="wld-input">
                                </div>
                            </div>
                            <input type="hidden" name="contacts[1][contact_type]" value="Finance">
                        </div>
                    </div>

                    @include('clients._onboarding-nav', ['step' => 3, 'totalSteps' => 5, 'back' => true, 'next' => 'nextStep()', 'nextLabel' => 'Continue'])
                </div>

                {{-- ══════════════════════════════════════
                     STEP 4 — Tech Contact
                ══════════════════════════════════════ --}}
                <div class="step-pane" id="pane-4">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-black tracking-tight">Tech Contact</h2>
                        <p class="text-sm text-gray-500 mt-1">Who manages your website, systems, or IT?</p>
                    </div>

                    <label class="same-toggle mb-4 block">
                        <input type="checkbox" id="tech_same_as_main" onchange="toggleSameAs('tech', this.checked)">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Same as Main Contact — copy details across
                    </label>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4" id="tech-fields">
                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Full Name</label>
                                    <input type="text" name="contacts[2][full_name]" id="tech_name"
                                           placeholder="Sam Lee" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Role / Title</label>
                                    <input type="text" name="contacts[2][role]"
                                           value="Technical Contact" placeholder="IT Manager" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Email Address</label>
                                    <input type="email" name="contacts[2][email]" id="tech_email"
                                           placeholder="tech@company.com" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Mobile</label>
                                    <input type="tel" name="contacts[2][phone]" id="tech_phone"
                                           placeholder="+61 4xx xxx xxx" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">LinkedIn URL</label>
                                    <input type="url" name="contacts[2][linkedin_url]"
                                           placeholder="https://linkedin.com/in/…" class="wld-input">
                                </div>
                            </div>
                            <input type="hidden" name="contacts[2][contact_type]" value="Technical">
                        </div>
                    </div>

                    @include('clients._onboarding-nav', ['step' => 4, 'totalSteps' => 6, 'back' => true, 'next' => 'nextStep()', 'nextLabel' => 'Continue'])
                </div>

                <div class="step-pane" id="pane-5">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-black tracking-tight">Services List</h2>
                        <p class="text-sm text-gray-500 mt-1">Check all the services required.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4" id="service-fields">
                        <div class="p-5 space-y-4">
                            <p class="text-xs font-bold text-black uppercase tracking-widest">
                                Services
                            </p>

                            @php
                                $services = config('services.offered_services');
                                sort($services);
                            @endphp

                                <!-- Grid Container for Two Sections -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach($services as $index => $service)
                                    <div class="space-y-3">
                                        <label class="flex items-center gap-3 border border-gray-200 rounded-md px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox"
                                                   name="services[]"
                                                   value="{{ $service }}"
                                                   class="accent-black"
                                                {{ collect(old('services'))->contains($service) ? 'checked' : '' }}>

                                            <span class="text-sm text-gray-800">{{ $service }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>


                    {{-- Final submit --}}
                    @include('clients._onboarding-nav', ['step' => 5, 'totalSteps' => 6, 'back' => true, 'next' => 'nextStep()', 'nextLabel' => 'Continue'])
                </div>

                {{-- ══════════════════════════════════════
                     STEP 5 — Employees
                ══════════════════════════════════════ --}}

                <div class="step-pane" id="pane-6">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold text-black tracking-tight">Employees List</h2>
                        <p class="text-sm text-gray-500 mt-1">Add staff members individually or upload a file with your contacts list.</p>
                    </div>

                    {{-- Manual employees --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
                        <div class="card-head">
                            <h3>Add Individually</h3>
                            <span class="text-xs text-black">Name, Email, Mobile</span>
                        </div>
                        <div class="p-5 space-y-3" id="employees-container">
                            <div class="employee-row">
                                <div>
                                    <label class="field-label">Full Name</label>
                                    <input type="text" name="employees[0][name]" placeholder="Jane Smith" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Email</label>
                                    <input type="email" name="employees[0][email]" placeholder="jane@company.com" class="wld-input">
                                </div>
                                <div>
                                    <label class="field-label">Mobile</label>
                                    <input type="tel" name="employees[0][phone]" placeholder="+61 4xx xxx xxx" class="wld-input">
                                </div>
                                <div style="padding-bottom: 0; display:flex; align-items:flex-end;">
                                    <div style="height:2rem"></div>
                                </div>
                            </div>
                        </div>
                        <div class="px-5 pb-5">
                            <button type="button" id="add-employee-btn" class="btn-outline-sm w-full justify-center mt-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                                Add another employee
                            </button>
                        </div>
                    </div>

                    {{-- Paste employees --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4">
                        <div class="card-head">
                            <h3>Paste Employees</h3>
                            <span class="text-xs text-black">Quick add from Excel, email, etc.</span>
                        </div>
                        <div class="p-5">
        <textarea id="paste-employees"
                  rows="4"
                  placeholder="Jane Smith, jane@company.com, 0412345678
John Doe | john@company.com | +61412345678"
                  class="wld-input w-full"></textarea>

                            <button type="button" id="parse-paste-btn" class="btn-outline-sm mt-3">
                                Paste & Add Employees
                            </button>

                            <p class="text-xs text-black mt-2">
                                Supported: comma (,) or pipe (|) separated → Name, Email, Mobile
                            </p>
                        </div>
                    </div>

                    {{-- Bulk upload --}}
                    {{-- Bulk upload --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                        <div class="card-head">
                            <h3>Bulk Upload</h3>
                            <span class="text-xs text-black">CSV or PDF accepted</span>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-lg py-8 group"
                                 onclick="document.getElementById('staff_contacts_file').click();"
                                 style="cursor: pointer;">
                                <svg class="w-8 h-8 text-gray-300 group-hover:text-yellow-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span class="text-sm font-medium text-gray-500">Click to upload staff contacts file</span>
                                <span class="text-xs text-black">CSV or PDF, max 10MB</span>
                                {{-- ✅ Input is now completely outside the clickable area --}}
                                <input type="file"
                                       id="staff_contacts_file"
                                       name="staff_contacts_file"
                                       accept=".csv,.pdf"
                                       class="hidden"
                                       onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''">
                            </div>
                            <p id="file-name" class="text-xs text-center text-black mt-2"></p>
                        </div>
                    </div>
                    {{-- Final submit --}}
                    <div class="flex justify-between pt-2 pb-6" style="position: relative; z-index: 10;">                        <button type="button" onclick="prevStep()" class="btn-ghost">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Back
                        </button>
                        <button type="submit" class="btn-primary" style="padding: 0.7rem 2rem;">
                            Submit Profile
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </main>
</div>

<script>
    let currentStep = 1;
    const totalSteps = 6;
    const pcts = ['16.67%', '33.33%', '50%', '66.67%', '83.33%', '100%']; // Updated percentages for each step
    const stepLabels = [
        'Step 1 of 6 — Company Information',
        'Step 2 of 6 — Main Contact',
        'Step 3 of 6 — Accounts Payable',
        'Step 4 of 6 — Tech Contact',
        'Step 5 of 6 — Services',
        'Step 6 of 6 — Employees',
    ];

    function updateUI() {
        // Panes
        document.querySelectorAll('.step-pane').forEach((p, i) => {
            p.classList.toggle('active', i + 1 === currentStep);
        });

        // Sidebar
        document.querySelectorAll('.step-nav-item').forEach((item, i) => {
            const step    = i + 1;
            const bubble  = item.querySelector('.step-bubble');
            const numEl   = item.querySelector('.step-num');
            const checkEl = item.querySelector('.step-check');
            const labelEl = item.querySelector('.step-label');

            item.classList.remove('is-active');
            bubble.classList.remove('step-bubble-active', 'step-bubble-done');
            labelEl.classList.remove('step-label-active');

            if (step === currentStep) {
                item.classList.add('is-active');
                bubble.classList.add('step-bubble-active');
                labelEl.classList.add('step-label-active');
                numEl.classList.remove('hidden');
                checkEl.classList.add('hidden');
            } else if (step < currentStep) {
                bubble.classList.add('step-bubble-done');
                numEl.classList.add('hidden');
                checkEl.classList.remove('hidden');
            } else {
                numEl.classList.remove('hidden');
                checkEl.classList.add('hidden');
            }
        });

        // Progress
        document.getElementById('progress-fill').style.width  = pcts[currentStep - 1];
        document.getElementById('progress-label').textContent = stepLabels[currentStep - 1];
        document.getElementById('progress-pct').textContent   = pcts[currentStep - 1];

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function nextStep() { if (currentStep < totalSteps) { currentStep++; updateUI(); } }
    function prevStep() { if (currentStep > 1) { currentStep--; updateUI(); } }

    // ── Same-as-main toggle ──
    function toggleSameAs(prefix, checked) {
        const srcName  = document.getElementById('main_name');
        const srcEmail = document.getElementById('main_email');
        const srcPhone = document.getElementById('main_phone');

        const tgtName  = document.getElementById(`${prefix}_name`);
        const tgtEmail = document.getElementById(`${prefix}_email`);
        const tgtPhone = document.getElementById(`${prefix}_phone`);

        const fields   = document.getElementById(`${prefix}-fields`);

        if (checked) {
            tgtName.value  = srcName?.value  || '';
            tgtEmail.value = srcEmail?.value || '';
            tgtPhone.value = srcPhone?.value || '';
            fields.style.opacity = '0.5';
            fields.style.pointerEvents = 'none';
        } else {
            fields.style.opacity = '';
            fields.style.pointerEvents = '';
        }
    }

    // ── Add website ──
    let websiteCount = 1;
    document.getElementById('add-website-btn').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'flex gap-2 website-row';
        row.innerHTML = `
            <input type="url" name="websites[]" placeholder="https://another.com" class="wld-input flex-1">
            <button type="button" class="btn-remove remove-website" title="Remove">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        row.querySelector('.remove-website').addEventListener('click', () => {
            row.style.opacity = '0'; row.style.transition = 'opacity 0.15s';
            setTimeout(() => row.remove(), 150);
        });
        document.getElementById('websites-container').appendChild(row);
        websiteCount++;
    });

    // ── Add employee ──
    let employeeCount = 1;
    document.getElementById('add-employee-btn').addEventListener('click', () => {
        const idx = employeeCount++;
        const row = document.createElement('div');
        row.className = 'employee-row';
        row.innerHTML = `
            <div>
                <label class="field-label">Full Name</label>
                <input type="text" name="employees[${idx}][name]" placeholder="Jane Smith" class="wld-input">
            </div>
            <div>
                <label class="field-label">Email</label>
                <input type="email" name="employees[${idx}][email]" placeholder="jane@company.com" class="wld-input">
            </div>
            <div>
                <label class="field-label">Mobile</label>
                <input type="tel" name="employees[${idx}][phone]" placeholder="+61 4xx xxx xxx" class="wld-input">
            </div>
            <div style="display:flex;align-items:flex-end;padding-bottom:0">
                <button type="button" class="btn-remove remove-employee" title="Remove">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>`;
        row.querySelector('.remove-employee').addEventListener('click', () => {
            row.style.opacity = '0'; row.style.transition = 'opacity 0.15s';
            setTimeout(() => row.remove(), 150);
        });
        document.getElementById('employees-container').appendChild(row);
    });

    updateUI();

    // Handle paste
    document.getElementById('parse-paste-btn').addEventListener('click', () => {
        const input = document.getElementById('paste-employees');
        const text = input.value.trim();

        if (!text) return;

        const lines = text.split('\n');
        let added = 0;

        lines.forEach(line => {
            const parts = line.split(/[,|]/).map(p => p.trim());
            if (parts.length < 3) return;

            let [name, email, phone] = parts;

            phone = normalisePhone(phone) || phone;

            const idx = employeeCount++;

            const row = document.createElement('div');
            row.className = 'employee-row';

            row.innerHTML = `
            <div>
                <label class="field-label">Full Name</label>
                <input type="text" name="employees[${idx}][name]" value="${name}" class="wld-input">
            </div>
            <div>
                <label class="field-label">Email</label>
                <input type="email" name="employees[${idx}][email]" value="${email}" class="wld-input">
            </div>
            <div>
                <label class="field-label">Mobile</label>
                <input type="tel" name="employees[${idx}][phone]" value="${phone}" class="wld-input">
            </div>
            <div style="display:flex;align-items:flex-end;padding-bottom:0">
                <button type="button" class="btn-remove remove-employee" title="Remove">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;

            row.querySelector('.remove-employee').addEventListener('click', () => {
                row.style.opacity = '0';
                row.style.transition = 'opacity 0.15s';
                setTimeout(() => row.remove(), 150);
            });

            document.getElementById('employees-container').appendChild(row);
            added++;
        });

        input.value = '';

        if (added) {
            alert(`${added} employee(s) added`);
        }
    });
    // Create row
    // function addEmployeeRow(name = '', email = '', phone = '') {
    //     const container = document.getElementById('employees-container');
    //
    //     const row = document.createElement('div');
    //     row.classList.add('employee-row');
    //
    //     row.innerHTML = `
    //     <div>
    //         <label class="field-label">Full Name</label>
    //         <input type="text" name="employees[${employeeIndex}][name]" value="${name}" class="wld-input">
    //     </div>
    //     <div>
    //         <label class="field-label">Email</label>
    //         <input type="email" name="employees[${employeeIndex}][email]" value="${email}" class="wld-input">
    //     </div>
    //     <div>
    //         <label class="field-label">Mobile</label>
    //         <input type="tel" name="employees[${employeeIndex}][phone]" value="${phone}" class="wld-input">
    //     </div>
    //     <div style="display:flex; align-items:flex-end;">
    //         <button type="button" onclick="this.closest('.employee-row').remove()" class="text-red-500 text-xs">
    //             Remove
    //         </button>
    //     </div>
    // `;
    //
    //     container.appendChild(row);
    //     employeeIndex++;
    // }

    function normalisePhone(raw) {
        if (!raw) return null;

        let n = raw.replace(/[\s\-().]/g, '');

        if (/^\+[1-9]\d{6,14}$/.test(n)) return n;
        if (/^04\d{8}$/.test(n)) return '+61' + n.slice(1);
        if (/^4\d{8}$/.test(n)) return '+61' + n;
        if (/^614\d{8}$/.test(n)) return '+' + n;
        if (/^0[2378]\d{8}$/.test(n)) return '+61' + n.slice(1);

        return null;
    }
</script>

</body>
</html>

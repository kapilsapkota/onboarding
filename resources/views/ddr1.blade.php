<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    @php
        $account = $stripeAccount ?? null;
        $company = $account?->company;
        $brand = $company?->name ?? 'All in IT Solutions';

        $stripeKey = $account?->publishable_key
            ?: config('services.stripe.key');

        $setupIntentUrl = $account
            ? route('ddr.account.setup-intent', $account->public_id)
            : route('onboarding.setup-intent');

        $submitUrl = $account
            ? route('ddr.account.store', $account->public_id)
            : route('onboarding.store');

        $logoUrl = $company?->logo
            ? asset('images/' . $company->logo)
            : asset('images/default-logo.png');

        $shareTitle = "Direct Debit Request | {$brand}";

        $shareDescription =
            "Securely set up your Direct Debit with {$brand}. " .
            "Your bank details are securely handled by Stripe.";
    @endphp

    <title>{{ $shareTitle }}</title>

    <meta
        name="description"
        content="{{ $shareDescription }}"
    >

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $shareTitle }}">
    <meta property="og:description" content="{{ $shareDescription }}">
    <meta property="og:image" content="{{ $logoUrl }}">
    <meta property="og:image:alt" content="{{ $brand }} logo">
    <meta property="og:site_name" content="{{ $brand }}">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $shareTitle }}">
    <meta name="twitter:description" content="{{ $shareDescription }}">
    <meta name="twitter:image" content="{{ $logoUrl }}">
    <meta name="twitter:image:alt" content="{{ $brand }} logo">

    <meta name="theme-color" content="#4f46e5">

    @vite(['resources/css/app.css'])

    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">

<div class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:py-12">

    <div class="w-full max-w-xl">

        {{-- Main Card --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 bg-white px-6 py-8 sm:px-8">

                {{-- Company Logo --}}
                @if($company?->logo)
                    <div class="flex h-16 w-full items-center justify-center sm:h-16">
                        <img
                            src="{{ asset('images/' . $company->logo) }}"
                            alt="{{ $brand }} logo"
                            class="max-h-24 max-w-full object-contain sm:max-h-16"
                        >
                    </div>
                @else
                    <div class="flex h-16 w-full items-center justify-center sm:h-16">
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-indigo-600 text-3xl font-bold text-white">
                            {{ strtoupper(substr($brand, 0, 1)) }}
                        </div>
                    </div>
                @endif


                {{-- Page Title --}}
                <div class="mt-7 text-center">

                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Direct Debit Request
                    </h1>

                    <p class="mx-auto mt-1.5 max-w-md text-sm leading-6 text-slate-500">
                        Set up your direct debit securely with {{ $brand }}.
                    </p>

                </div>

            </div>

            <div class="px-6 py-6 sm:px-8">

                <form id="ddrForm" method="POST" action="{{ $submitUrl }}">
                    @csrf

                    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>

                            <label
                                for="full_name"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                id="full_name"
                                name="contacts[0][full_name]"
                                placeholder="Full name"
                                autocomplete="name"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                            >

                            <input
                                type="hidden"
                                name="contacts[0][contact_type]"
                                value="Main Contact"
                            >

                            <input
                                type="hidden"
                                name="contacts[0][is_primary]"
                                value="1"
                            >
                        </div>

                        <div>

                            <label
                                for="company_name"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Company Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                id="company_name"
                                name="company_name"
                                placeholder="Company name"
                                autocomplete="organization"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                            >
                        </div>
                    </div>

                    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <div>
                            <label
                                for="email"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Email
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="contacts[0][email]"
                                placeholder="you@example.com"
                                autocomplete="email"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                            >
                        </div>


                        <div>
                            <label
                                for="mobile"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Mobile
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="tel"
                                id="mobile"
                                name="contacts[0][phone]"
                                placeholder="Mobile number"
                                autocomplete="tel"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                            >
                        </div>

                    </div>


                    {{-- ====================================================
                         ACCOUNT NAME
                    ===================================================== --}}
                    <div class="mb-5">

                        <label
                            for="account_name"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Account Name
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            id="account_name"
                            name="account_name"
                            placeholder="Name on bank account"
                            autocomplete="off"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                        >

                        <p class="mt-1.5 text-xs text-slate-500">
                            Must match the bank account holder name.
                        </p>
                    </div>


                    {{-- ====================================================
                         BANK DETAILS / STRIPE
                    ===================================================== --}}
                    <div class="mb-5">

                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            Bank account
                            <span class="text-red-500">*</span>
                        </label>

                        <div
                            id="becs-bank-element"
                            class="min-h-12 rounded-lg border border-slate-300 bg-white px-3.5 py-3 shadow-sm transition focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10"
                        ></div>

                        <div
                            id="becs-error"
                            class="mt-1.5 text-xs text-red-600"
                        ></div>

                        <p class="mt-1.5 text-xs text-slate-500">
                            Enter your 6-digit BSB and account number.
                        </p>
                    </div>


                    {{-- ====================================================
                         HIDDEN STRIPE FIELDS
                    ===================================================== --}}
                    <input
                        type="hidden"
                        name="stripe_payment_method_id"
                        id="stripe_payment_method_id"
                    >

                    <input
                        type="hidden"
                        name="stripe_customer_id"
                        id="stripe_customer_id"
                    >

                    <input
                        type="hidden"
                        name="stripe_setup_intent_id"
                        id="stripe_setup_intent_id"
                    >

                    <input
                        type="hidden"
                        name="bsb"
                        id="hidden_bsb"
                    >

                    <input
                        type="hidden"
                        name="account_number"
                        id="hidden_account_number"
                    >


                    {{-- ====================================================
                         AGREEMENT
                    ===================================================== --}}
                    <div class="my-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

                        <div class="flex gap-3">

                            {{-- Info Icon --}}
                            <div class="shrink-0">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                                    <svg
                                        class="h-4 w-4 text-amber-700"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 100-20 10 10 0 000 20z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <div class="text-xs leading-5 text-amber-900">

                                <p>
                                    You agree to this Direct Debit Request and the Direct Debit
                                    Request service agreement, and authorise
                                    <strong>Stripe Payments Australia Pty Ltd</strong>
                                    ACN 160 180 343 Direct Debit User ID number 507156 ("Stripe")
                                    to debit your account through the Bulk Electronic Clearing
                                    System (BECS) on behalf of
                                    <strong>{{ $brand }}</strong>
                                    (the "Merchant") for any amounts separately communicated
                                    to you by the Merchant.
                                </p>

                                <p class="mt-3">
                                    You certify that you are either an account holder or an
                                    authorised signatory on the account listed above.
                                </p>

                                <a
                                    href="https://stripe.com/au/legal/becs-dd-service-agreement"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-3 inline-flex items-center gap-1 font-medium text-amber-700 underline underline-offset-2 hover:text-amber-900"
                                >
                                    View Direct Debit Service Agreement

                                    <svg
                                        class="h-3 w-3"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                        />
                                    </svg>
                                </a>

                            </div>
                        </div>
                    </div>

                    <div id="form-feedback" class="mb-4"></div>

                    <button
                        type="submit"
                        id="submitBtn"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:bg-slate-400"
                    >

                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2V9a6 6 0 00-12 0v10a2 2 0 002 2z"
                            />
                        </svg>

                        <span id="submitText">
                            Submit Direct Debit Request
                        </span>

                    </button>


                    {{-- Security note --}}
                    <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-slate-400">

                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2V9a6 6 0 00-12 0v10a2 2 0 002 2z"
                            />
                        </svg>

                        Your bank details are securely handled by Stripe.
                    </div>

                </form>
            </div>
        </div>


        {{-- Footer --}}
        <p class="mt-5 text-center text-xs text-slate-400">
            Direct Debit setup for {{ $brand }} (via Stripe)
        </p>

    </div>
</div>


<script>
    const form = document.getElementById('ddrForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const feedback = document.getElementById('form-feedback');

    let becsComplete = false;

    // Initialize Stripe
    const stripe = Stripe('{{ $stripeKey }}');
    const elements = stripe.elements();

    // Create AU BECS bank account element
    const becsElement = elements.create('auBankAccount', {
        style: {
            base: {
                color: '#111827',
                fontSize: '16px',
                fontFamily: 'Inter, sans-serif',
                '::placeholder': {
                    color: '#94a3b8'
                }
            }
        }
    });

    becsElement.mount('#becs-bank-element');


    // Stripe element changes
    becsElement.on('change', (e) => {

        becsComplete = e.complete;

        const errorEl = document.getElementById('becs-error');

        errorEl.textContent = e.error
            ? e.error.message
            : '';

        if (e.value?.bsbNumber) {
            document.getElementById('hidden_bsb').value =
                e.value.bsbNumber;
        }
    });


    function showError(msg) {

        feedback.innerHTML = `
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="flex items-center gap-2">
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v3m0 4h.01M10.29 3.86l-7.82 13a2 2 0 001.71 3h15.64a2 2 0 001.71-3l-7.82-13a2 2 0 00-3.42 0z"
                        />
                    </svg>

                    <span>${msg}</span>
                </div>
            </div>
        `;
    }


    function clearFeedback() {
        feedback.innerHTML = '';
    }


    // Form submission
    form.addEventListener('submit', async function(e) {

        e.preventDefault();

        clearFeedback();

        const fullName =
            document.getElementById('full_name').value.trim();

        const companyName =
            document.getElementById('company_name').value.trim();

        const email =
            document.getElementById('email').value.trim();

        const mobile =
            document.getElementById('mobile').value.trim();

        const accountName =
            document.getElementById('account_name').value.trim();


        // Validation
        if (!fullName) {
            showError('Name is required');
            return;
        }

        if (!companyName) {
            showError('Company Name is required');
            return;
        }

        if (!email) {
            showError('Email is required');
            return;
        }

        if (!email.includes('@')) {
            showError('Please enter a valid email address');
            return;
        }

        if (!mobile) {
            showError('Mobile is required');
            return;
        }

        if (!accountName) {
            showError('Account Name is required');
            return;
        }


        // Stripe validation
        if (!becsComplete) {

            document.getElementById('becs-error').textContent =
                'Please enter a valid BSB and Account Number';

            return;
        }


        // Existing payment method
        const existingPmId =
            document.getElementById('stripe_payment_method_id').value;

        if (existingPmId) {
            form.submit();
            return;
        }


        // Processing state
        submitBtn.disabled = true;

        submitText.textContent =
            'Setting up mandate...';


        try {

            // Create SetupIntent
            const siRes = await fetch('{{ $setupIntentUrl }}', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.content || '{{ csrf_token() }}'
                },

                body: JSON.stringify({
                    company_name: companyName,
                    billing_email: email,
                    billing_name: fullName,
                })
            });


            const {
                client_secret,
                customer_id,
                error: siError
            } = await siRes.json();


            if (siError) {
                throw new Error(siError);
            }


            // Store customer ID
            document.getElementById(
                'stripe_customer_id'
            ).value = customer_id;


            // Confirm SetupIntent
            const {
                setupIntent,
                error
            } = await stripe.confirmAuBecsDebitSetup(
                client_secret,
                {
                    payment_method: {
                        au_becs_debit: becsElement,

                        billing_details: {
                            name: accountName || companyName,
                            email: email,
                        },
                    },
                }
            );


            if (error) {
                throw new Error(error.message);
            }


            // Store Stripe IDs
            document.getElementById(
                'stripe_payment_method_id'
            ).value = setupIntent.payment_method;

            document.getElementById(
                'stripe_setup_intent_id'
            ).value = setupIntent.id;


            // Submit existing form
            form.submit();

        } catch (err) {

            document.getElementById(
                'becs-error'
            ).textContent = err.message;

            showError(err.message);

            submitBtn.disabled = false;

            submitText.textContent =
                'Submit Direct Debit Request';
        }
    });
</script>

</body>
</html>

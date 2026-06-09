<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Direct Debit Request | All in IT Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header {
            padding: 1.75rem 2rem 0.5rem 2rem;
            border-bottom: 1px solid #eef2f6;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1e2b;
        }

        .header .via {
            font-size: 0.75rem;
            color: #b8860b;
            margin-top: 0.25rem;
        }

        .form-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-body {
                padding: 1.5rem;
            }
            .header {
                padding: 1.25rem 1.5rem 0.25rem 1.5rem;
            }
        }

        label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5b677b;
            margin-bottom: 0.5rem;
        }

        .required {
            color: #e03a3a;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.5rem;
            outline: none;
            transition: all 0.2s;
        }

        input:focus {
            border-color: #c9a84c;
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.1);
        }

        /* Stripe Element styling - same as your onboarding form */
        .stripe-element-container {
            width: 100%;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.7rem 1rem;
            min-height: 3.25rem;
            transition: all 0.2s;
        }

        .stripe-element-container:focus-within {
            border-color: #c9a84c;
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.1);
        }

        .agreement {
            background: #fefaf0;
            border-left: 3px solid #c9a84c;
            padding: 0.9rem 1rem;
            font-size: 0.7rem;
            line-height: 1.5;
            color: #3b3f4b;
            margin: 1.5rem 0;
            border-radius: 0.5rem;
        }

        .agreement a {
            color: #c9a84c;
            text-decoration: none;
        }

        .agreement a:hover {
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            background: #c9a84c;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.85rem;
            border: none;
            border-radius: 2rem;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: #b8973c;
        }

        .btn-submit:disabled {
            background: #b9ad8a;
            cursor: not-allowed;
        }

        .error-message {
            color: #e03a3a;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }

        .field-hint {
            font-size: 0.65rem;
            color: #8c9ab3;
            margin-top: 0.3rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Direct Debit Request</h1>
        <div class="via">with All in IT Solutions (Via Stripe)</div>
    </div>

    <div class="form-body">
        <form id="ddrForm" method="POST" action="{{ route('onboarding.store') }}">
            @csrf

            <!-- Name (Required) - maps to contacts[0][full_name] like your onboarding form -->
            <div class="form-group">
                <label>Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="contacts[0][full_name]" placeholder="Full Name" required>
                <input type="hidden" name="contacts[0][contact_type]" value="Main Contact">
                <input type="hidden" name="contacts[0][is_primary]" value="1">
            </div>

            <!-- Company Name (Required) -->
            <div class="form-group">
                <label>Company Name <span class="required">*</span></label>
                <input type="text" id="company_name" name="company_name" placeholder="Company Name" required>
            </div>

            <!-- Email + Mobile row - maps to contacts[0][email] and contacts[0][phone] -->
            <div class="form-row">
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" id="email" name="contacts[0][email]" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <label>Mobile <span class="required">*</span></label>
                    <input type="tel" id="mobile" name="contacts[0][phone]" placeholder="Mobile" required>
                </div>
            </div>

            <!-- Account Name (Required) - for bank account -->
            <div class="form-group">
                <label>Account Name <span class="required">*</span></label>
                <input type="text" id="account_name" name="account_name" placeholder="Account Name" required>
                <div class="field-hint">Must match the bank account holder name</div>
            </div>

            <!-- BSB & Account Number using Stripe Element (same as your onboarding form) -->
            <div class="form-group">
                <label>BSB <span class="required">*</span></label>
                <div id="becs-bank-element" class="stripe-element-container"></div>
                <div id="becs-error" class="error-message"></div>
                <div class="field-hint">Enter your 6-digit BSB (e.g., 123456 or 123-456)</div>
            </div>

            <!-- Hidden fields for Stripe data (same as your onboarding form) -->
            <input type="hidden" name="stripe_payment_method_id" id="stripe_payment_method_id">
            <input type="hidden" name="stripe_customer_id" id="stripe_customer_id">
            <input type="hidden" name="stripe_setup_intent_id" id="stripe_setup_intent_id">

            <!-- Hidden fields for bank details (your store() expects these) -->
            <input type="hidden" name="bsb" id="hidden_bsb">
            <input type="hidden" name="account_number" id="hidden_account_number">

            <!-- Agreement text exactly from your image -->
            <div class="agreement">
                You agree to this Direct Debit Request and the Direct Debit Request service agreement, and authorise
                <strong>Stripe Payments Australia Pty Ltd</strong> ACN 160 180 343 Direct Debit User ID number 507156 ("Stripe")
                to debit your account through the Bulk Electronic Clearing System (BECS) on behalf of
                <strong>All in IT Solutions</strong> (the "Merchant") for any amounts separately communicated to you by the Merchant.
                <br><br>
                You certify that you are either an account holder or an authorised signatory on the account listed above.
                <br><br>
                <a href="https://stripe.com.au/legal/becs-dd-service-agreement" target="_blank">https://stripe.com.au/legal/becs-dd-service-agreement</a>
            </div>

            <div id="form-feedback"></div>
            <button type="submit" class="btn-submit" id="submitBtn">Submit Direct Debit Request</button>
        </form>
    </div>
</div>

<script>
    const form = document.getElementById('ddrForm');
    const submitBtn = document.getElementById('submitBtn');
    const feedback = document.getElementById('form-feedback');

    let becsComplete = false;

    // Initialize Stripe (same as your onboarding form)
    const stripe = Stripe('{{ config("services.stripe.key") }}');
    const elements = stripe.elements();

    // Create auBankAccount Element (same as your onboarding form)
    const becsElement = elements.create('auBankAccount', {
        style: {
            base: {
                color: '#111827',
                fontSize: '16px',
                fontFamily: 'Inter, sans-serif',
                '::placeholder': { color: '#9ca3af' },
            }
        }
    });

    becsElement.mount('#becs-bank-element');

    // Handle Stripe element changes (same as your onboarding form)
    becsElement.on('change', (e) => {
        becsComplete = e.complete;

        const errorEl = document.getElementById('becs-error');
        errorEl.textContent = e.error ? e.error.message : '';

        if (e.value?.bsbNumber) {
            document.getElementById('hidden_bsb').value = e.value.bsbNumber;
        }
    });

    function showError(msg) {
        feedback.innerHTML = '<div style="background:#fee2e2; color:#e03a3a; padding:0.75rem; border-radius:0.5rem; font-size:0.8rem; margin-bottom:1rem;">⚠️ ' + msg + '</div>';
    }

    function clearFeedback() {
        feedback.innerHTML = '';
    }

    // Form submission (same pattern as your onboarding form)
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        clearFeedback();

        // Get form values
        const fullName = document.getElementById('full_name').value.trim();
        const companyName = document.getElementById('company_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const mobile = document.getElementById('mobile').value.trim();
        const accountName = document.getElementById('account_name').value.trim();

        // Validate required fields
        if (!fullName) { showError('Name is required'); return; }
        if (!companyName) { showError('Company Name is required'); return; }
        if (!email) { showError('Email is required'); return; }
        if (!email.includes('@')) { showError('Valid email is required'); return; }
        if (!mobile) { showError('Mobile is required'); return; }
        if (!accountName) { showError('Account Name is required'); return; }

        // Check if Stripe element is complete
        if (!becsComplete) {
            document.getElementById('becs-error').textContent = 'Please enter a valid BSB and Account Number';
            return;
        }

        // Check if already have payment method
        const existingPmId = document.getElementById('stripe_payment_method_id').value;
        if (existingPmId) {
            form.submit();
            return;
        }

        // Disable button and show processing
        submitBtn.disabled = true;
        submitBtn.textContent = 'Setting up mandate...';

        try {
            // Step 1: Create SetupIntent (calls your existing endpoint - same as onboarding)
            const siRes = await fetch('{{ route("onboarding.setup-intent") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    company_name: companyName,
                    billing_email: email,
                })
            });

            const { client_secret, customer_id, error: siError } = await siRes.json();

            if (siError) throw new Error(siError);

            // Store customer ID
            document.getElementById('stripe_customer_id').value = customer_id;

            // Step 2: Confirm SetupIntent with the bank account element (same as your onboarding)
            const { setupIntent, error } = await stripe.confirmAuBecsDebitSetup(client_secret, {
                payment_method: {
                    au_becs_debit: becsElement,
                    billing_details: {
                        name: accountName || companyName,
                        email: email,
                    },
                },
            });

            if (error) throw new Error(error.message);

            // Store payment method and setup intent IDs
            document.getElementById('stripe_payment_method_id').value = setupIntent.payment_method;
            document.getElementById('stripe_setup_intent_id').value = setupIntent.id;

            // Submit the form to your existing store() method
            form.submit();

        } catch (err) {
            document.getElementById('becs-error').textContent = err.message;
            showError(err.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Direct Debit Request';
        }
    });
</script>

</body>
</html>

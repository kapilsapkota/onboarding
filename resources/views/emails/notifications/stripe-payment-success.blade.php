@extends('emails.layout', [
    'logoUrl' => $logoUrl ?? asset('images/allinit.png'),
    'brandName' => $companyName ?? config('app.name'),
    'bandColor' => '#16a34a',
    'eyebrow' => 'STRIPE PAYMENT',
    'emailTitle' => 'Payment Successful',
    'subtitle' => 'The payment has been successfully processed.',
    'badge' => '&#10003; SUCCEEDED',
    'preheader' => 'Stripe payment successful: '.($item->formattedAmount() ?? '').' — details inside.',
    'footerBrand' => $companyName ?? config('app.name'),
    'footerNote' => 'This is an automated Stripe payment notification.',
])

@section('emailBody')
{{-- Amount --}}
<tr>
    <td class="email-padding" style="padding: 30px 32px 20px; text-align: center;">
        <div style="font-size: 13px; color: #6b7280;">
            PAYMENT AMOUNT
        </div>

        <div style="font-size: 36px; font-weight: 700; color: #111827; margin-top: 6px;">
            {{ $item->formattedAmount() }}
        </div>

        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
            {{ strtoupper($item->currency ?? 'AUD') }}
        </div>
    </td>
</tr>

{{-- Customer --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Customer
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Customer
                </td>
                <td style="padding: 7px 0; font-weight: 600;">
                    {{ $item->stripeCustomer?->name ?? 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Stripe Customer
                </td>
                <td style="padding: 7px 0;">
                    {{ $item->stripe_customer_id ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Batch --}}
<tr>
    <td class="email-padding" style="padding: 20px 32px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Batch
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Reference
                </td>
                <td style="padding: 7px 0; font-weight: 600;">
                    {{ $item->batch?->reference ?? 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Batch ID
                </td>
                <td style="padding: 7px 0;">
                    #{{ $item->batch_id }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Stripe Account
                </td>
                <td style="padding: 7px 0; font-weight: 600;">
                    {{ $accountLabel ?? 'Legacy pool' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Company
                </td>
                <td style="padding: 7px 0;">
                    {{ $companyName ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Payment --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Payment Details
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Payment Intent
                </td>
                <td style="padding: 7px 0; word-break: break-all;">
                    {{ $item->stripe_payment_intent_id ?? 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Payment Method
                </td>
                <td style="padding: 7px 0;">
                    {{ $item->stripePaymentMethod?->type ?? 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Description
                </td>
                <td style="padding: 7px 0;">
                    {{ $item->description ?: 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Status
                </td>
                <td style="padding: 7px 0;">
                    <span style="
                        display: inline-block;
                        padding: 4px 10px;
                        border-radius: 999px;
                        background: #dcfce7;
                        color: #166534;
                        font-size: 12px;
                        font-weight: 700;
                    ">
                        SUCCEEDED
                    </span>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Processed at --}}
<tr>
    <td class="email-padding" style="padding: 0 32px 24px; font-size: 12px; color: #6b7280;">
        Processed at
        <span style="color: #374151;">
            {{ $item->processed_at?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}
        </span>
    </td>
</tr>
@endsection

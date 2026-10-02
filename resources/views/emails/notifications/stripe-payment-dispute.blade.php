@extends('emails.layout', [
    'logoUrl' => $logoUrl ?? asset('images/allinit.png'),
    'brandName' => $companyName ?? config('app.name'),
    'bandColor' => '#d97706',
    'eyebrow' => 'STRIPE PAYMENT',
    'emailTitle' => 'Payment Disputed',
    'subtitle' => 'A payment has been disputed and may require your attention.',
    'badge' => '&#9888; '.strtoupper((string) ($humanReadableStatus ?? 'DISPUTED')),
    'preheader' => 'Stripe payment disputed: '.($disputeAmount ?? '').' — details inside.',
    'footerBrand' => $companyName ?? config('app.name'),
    'footerNote' => 'This is an automated Stripe payment notification.',
])

@section('emailBody')
{{-- Amount --}}
<tr>
    <td class="email-padding" style="padding: 30px 32px 20px; text-align: center;">
        <div style="font-size: 13px; color: #6b7280;">
            DISPUTED AMOUNT
        </div>

        <div style="font-size: 36px; font-weight: 700; color: #92400e; margin-top: 6px;">
            {{ $disputeAmount }}
        </div>

        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
            {{ strtoupper($item->currency ?? 'AUD') }}
        </div>
    </td>
</tr>

{{-- Warning --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <table width="100%" cellpadding="0" cellspacing="0"
               style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px;">
            <tr>
                <td style="padding: 16px 18px;">
                    <div style="font-size: 14px; font-weight: 700; color: #92400e; margin-bottom: 6px;">
                        Action may be required
                    </div>

                    <div style="font-size: 13px; line-height: 1.6; color: #78350f;">
                        Please review this dispute in Stripe and take any required
                        action before the response deadline.
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Dispute --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Dispute Details
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Reason
                </td>
                <td style="padding: 7px 0; font-weight: 600;">
                    {{ $humanReadableReason }}
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
                        background: #fef3c7;
                        color: #92400e;
                        font-size: 12px;
                        font-weight: 700;
                    ">
                        {{ $humanReadableStatus }}
                    </span>
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Dispute ID
                </td>
                <td style="padding: 7px 0; word-break: break-all;">
                    {{ $dispute['id'] ?? 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Network Reason
                </td>
                <td style="padding: 7px 0;">
                    {{ $dispute['network_reason'] ?? 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Created
                </td>
                <td style="padding: 7px 0;">
                    {{ $createdAt }}
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Customer --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Customer
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Customer
                </td>
                <td style="padding: 7px 0; font-weight: 600;">
                    {{ $customerName }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Stripe Customer
                </td>
                <td style="padding: 7px 0; word-break: break-all;">
                    {{ $item->stripe_customer_id ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Batch --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
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

{{-- Original Payment --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Original Payment
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Amount
                </td>
                <td style="padding: 7px 0; font-weight: 600;">
                    {{ $item->formattedAmount() }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Currency
                </td>
                <td style="padding: 7px 0;">
                    {{ strtoupper($item->currency ?? 'AUD') }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
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
                    Payment Status
                </td>
                <td style="padding: 7px 0;">
                    <span style="
                        display: inline-block;
                        padding: 4px 10px;
                        border-radius: 999px;
                        background: #fef3c7;
                        color: #92400e;
                        font-size: 12px;
                        font-weight: 700;
                    ">
                        DISPUTED
                    </span>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Stripe --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Stripe
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Charge
                </td>
                <td style="padding: 7px 0; word-break: break-all;">
                    {{ $dispute['charge'] ?? 'N/A' }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Network Reason
                </td>
                <td style="padding: 7px 0;">
                    {{ $dispute['network_reason'] ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Dispute received at --}}
<tr>
    <td class="email-padding" style="padding: 0 32px 24px; font-size: 12px; color: #6b7280;">
        Dispute received at
        <span style="color: #374151;">
            {{ $createdAt }}
        </span>
    </td>
</tr>
@endsection

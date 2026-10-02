@extends('emails.layout', [
    'logoUrl' => $logoUrl ?? asset('images/allinit.png'),
    'brandName' => $companyName ?? config('app.name'),
    'bandColor' => '#16a34a',
    'eyebrow' => 'STRIPE PAYOUT',
    'emailTitle' => 'Payout Successful',
    'subtitle' => 'The payout has been successfully processed by Stripe.',
    'badge' => '&#10003; PAID',
    'preheader' => 'Stripe payout successful: '.($amount ?? '').' — details inside.',
    'footerBrand' => $companyName ?? config('app.name'),
    'footerNote' => 'This is an automated Stripe payout notification.',
])

@section('emailBody')
{{-- Amount --}}
<tr>
    <td class="email-padding" style="padding: 30px 32px 20px; text-align: center;">
        <div style="font-size: 13px; color: #6b7280;">
            PAYOUT AMOUNT
        </div>

        <div style="font-size: 36px; font-weight: 700; color: #111827; margin-top: 6px;">
            {{ $amount }}
        </div>

        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
            {{ $currency }}
        </div>
    </td>
</tr>

{{-- Payout Details --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Payout Details
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Payout ID
                </td>
                <td style="padding: 7px 0; word-break: break-all;">
                    {{ $payoutId }}
                </td>
            </tr>

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Currency
                </td>
                <td style="padding: 7px 0;">
                    {{ $currency }}
                </td>
            </tr>

            @if ($arrivalDate)
                <tr>
                    <td style="padding: 7px 0; color: #6b7280;">
                        Arrival Date
                    </td>
                    <td style="padding: 7px 0;">
                        {{ $arrivalDate }}
                    </td>
                </tr>
            @endif

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
                        PAID
                    </span>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Stripe Information --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 20px;">
        <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 12px;">
            Stripe Information
        </div>

        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 7px 0; color: #6b7280; width: 40%;">
                    Payout Status
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

            <tr>
                <td style="padding: 7px 0; color: #6b7280;">
                    Stripe Payout
                </td>
                <td style="padding: 7px 0; word-break: break-all;">
                    {{ $payoutId }}
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

{{-- Processed at --}}
<tr>
    <td class="email-padding" style="padding: 0 32px 24px; font-size: 12px; color: #6b7280;">
        Processed at
        <span style="color: #374151;">
            {{ now()->format('d/m/Y H:i:s') }}
        </span>
    </td>
</tr>
@endsection

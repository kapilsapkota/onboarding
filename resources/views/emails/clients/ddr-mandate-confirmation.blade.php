@extends('emails.layout', [
    'logoUrl' => $logoUrl ?? null,
    'brandName' => $companyName ?? config('app.name'),
    'bandColor' => '#1d4ed8',
    'eyebrow' => 'DIRECT DEBIT REQUEST',
    'emailTitle' => 'Authority Confirmed',
    'subtitle' => 'Your direct debit authority has been recorded.',
    'badge' => '&#10003; '.strtoupper((string) ($client->mandate_status ?? 'pending')),
    'preheader' => 'Confirmation of your direct debit authority with '.($companyName ?? config('app.name')).' — please keep this email for your records.',
    'footerBrand' => $companyName ?? config('app.name'),
    'footerNote' => 'Please keep this email as your record of this authority. If anything above looks wrong, contact us straight away using the details in this email.',
])

@section('emailBody')
@php
    $maskedAccount = $client->account_number ? '•••• '.substr((string) $client->account_number, -4) : 'N/A';
    $merchant = $companyName ?? config('app.name');
    $contactName = $client->primary_contact_name;
@endphp

{{-- Intro --}}
<tr>
    <td class="email-padding" style="padding: 26px 32px 6px; font-size: 14px; line-height: 1.6; color: #374151;">
        Hi{{ $contactName ? ' '.$contactName : '' }},<br><br>
        Thank you — your Direct Debit Request has been received and your authority below is now recorded with
        <strong style="color: #111827;">{{ $merchant }}</strong>@isset($submittedVia)
        (submitted via {{ $submittedVia }})@endisset.
        Debits will be drawn from your nominated account under this authority.
    </td>
</tr>

{{-- Authority details --}}
<tr>
    <td class="email-padding" style="padding: 14px 32px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px;">
            Your Authority Details
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; width: 42%; vertical-align: top;">
                    Debit user (merchant)
                </td>
                <td style="padding: 8px 0; font-size: 13px; font-weight: 700; color: #111827;">
                    {{ $merchant }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                    Authorised by
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827;">
                    {{ $client->account_name ?? $client->company_name ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                    BSB
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827;">
                    {{ $client->bsb ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                    Account number
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827; letter-spacing: 1px;">
                    {{ $maskedAccount }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                    Authority reference
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827;">
                    DDR-{{ $client->id }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                    Date authorised
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827;">
                    {{ $client->created_at?->format('d M Y') ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Scope of authority --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;">
            <tr>
                <td style="padding: 18px 20px; font-size: 13px; line-height: 1.7; color: #1e3a8a;">
                    <div style="font-size: 12px; letter-spacing: 1.5px; font-weight: 700; color: #1d4ed8; margin-bottom: 8px;">
                        WHAT THIS AUTHORITY COVERS
                    </div>
                    All invoices issued and communicated to you by
                    <strong>{{ $merchant }}</strong> will be debited to the above account under this authority.
                    Each debit will match the invoiced amount and will fall due in line with your invoice payment terms.
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Customer rights --}}
<tr>
    <td class="email-padding" style="padding: 18px 32px 6px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 10px;">
            Your Rights
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
            <tr>
                <td width="28" style="vertical-align: top; padding: 5px 0;">
                    <span style="display: inline-block; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700;">1</span>
                </td>
                <td style="font-size: 13px; color: #475569; padding: 5px 0 5px 6px; line-height: 1.6;">
                    <strong style="color: #111827;">Change or cancel.</strong>
                    You may vary or cancel this authority at any time by contacting us — please allow reasonable time for your request to be actioned before the next debit.
                </td>
            </tr>
            <tr>
                <td width="28" style="vertical-align: top; padding: 5px 0;">
                    <span style="display: inline-block; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700;">2</span>
                </td>
                <td style="font-size: 13px; color: #475569; padding: 5px 0 5px 6px; line-height: 1.6;">
                    <strong style="color: #111827;">Stop a debit via your bank.</strong>
                    You can also arrange with your own financial institution to stop an individual debit or cancel this authority.
                </td>
            </tr>
            <tr>
                <td width="28" style="vertical-align: top; padding: 5px 0;">
                    <span style="display: inline-block; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700;">3</span>
                </td>
                <td style="font-size: 13px; color: #475569; padding: 5px 0 5px 6px; line-height: 1.6;">
                    <strong style="color: #111827;">Disputes and incorrect debits.</strong>
                    If you believe a debit is incorrect or unauthorised, contact us first. If the matter is not resolved, your financial institution can investigate and arrange a refund where appropriate.
                </td>
            </tr>
            <tr>
                <td width="28" style="vertical-align: top; padding: 5px 0;">
                    <span style="display: inline-block; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700;">4</span>
                </td>
                <td style="font-size: 13px; color: #475569; padding: 5px 0 5px 6px; line-height: 1.6;">
                    <strong style="color: #111827;">Advance notice.</strong>
                    We will give you at least 14 days' notice before any change to these debit arrangements, unless you agree to a shorter period.
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Merchant contact --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px 28px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <tr>
                <td style="padding: 18px 20px;">
                    <div style="font-size: 12px; letter-spacing: 1.5px; font-weight: 700; color: #64748b; margin-bottom: 10px;">
                        QUESTIONS ABOUT YOUR DEBITS?
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827;">
                        {{ $merchant }}
                    </div>
                    <div style="font-size: 13px; color: #475569; margin-top: 4px; line-height: 1.7;">
                        @if($client->company?->email)
                            <a href="mailto:{{ $client->company->email }}" style="color: #1d4ed8; text-decoration: none;">{{ $client->company->email }}</a><br>
                        @endif
                        @if($client->company?->phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', (string) $client->company->phone) }}" style="color: #475569; text-decoration: none;">{{ $client->company->phone }}</a>
                        @endif
                        @if(! $client->company?->email && ! $client->company?->phone)
                            Please reply to the sender of this email and we will help.
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>
@endsection

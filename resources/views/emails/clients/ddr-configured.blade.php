@extends('emails.layout', [
    'logoUrl' => $logoUrl ?? null,
    'brandName' => $companyName ?? config('app.name'),
    'bandColor' => '#16a34a',
    'eyebrow' => 'DIRECT DEBIT',
    'emailTitle' => 'Configured Successfully',
    'subtitle' => 'A new direct debit authority is ready to go.',
    'badge' => '&#10003; '.strtoupper((string) ($client->mandate_status ?? 'pending')),
    'preheader' => 'Direct debit authority set up for '.($client->company_name ?? 'a new client').' — details inside.',
    'footerBrand' => $companyName ?? config('app.name'),
])

@section('emailBody')
@php
    $maskedAccount = $client->account_number ? '•••• '.substr((string) $client->account_number, -4) : 'N/A';
@endphp

{{-- Intro --}}
<tr>
    <td class="email-padding" style="padding: 26px 32px 6px; font-size: 14px; line-height: 1.6; color: #374151;">
        Hi team,<br><br>
        A direct debit authority has been set up for
        <strong style="color: #111827;">{{ $client->company_name ?? 'Unknown Company' }}</strong>@isset($submittedVia)
        via {{ $submittedVia }}@endisset.
        The confirmed details are below for your records.
    </td>
</tr>

{{-- Authority details --}}
<tr>
    <td class="email-padding" style="padding: 14px 32px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px;">
            Authority Details
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; width: 42%; vertical-align: top;">
                    Client
                </td>
                <td style="padding: 8px 0; font-size: 13px; font-weight: 700; color: #111827;">
                    {{ $client->company_name ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                    Account name
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827;">
                    {{ $client->account_name ?? 'N/A' }}
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
                    Submitted
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827;">
                    {{ $client->created_at?->format('d M Y, H:i') ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Contact --}}
<tr>
    <td class="email-padding" style="padding: 10px 32px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <tr>
                <td style="padding: 18px 20px;">
                    <div style="font-size: 12px; letter-spacing: 1.5px; font-weight: 700; color: #64748b; margin-bottom: 10px;">
                        PRIMARY CONTACT
                    </div>
                    <div style="font-size: 15px; font-weight: 700; color: #111827;">
                        {{ $client->primary_contact_name ?? 'N/A' }}
                    </div>
                    <div style="font-size: 13px; color: #475569; margin-top: 4px;">
                        @if($client->primary_email)
                            <a href="mailto:{{ $client->primary_email }}" style="color: #16a34a; text-decoration: none;">{{ $client->primary_email }}</a>
                        @endif
                        @if($client->primary_email && $client->primary_phone)
                            <span style="color: #cbd5e1;">&nbsp;·&nbsp;</span>
                        @endif
                        @if($client->primary_phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', (string) $client->primary_phone) }}" style="color: #475569; text-decoration: none;">{{ $client->primary_phone }}</a>
                        @endif
                        @if(! $client->primary_email && ! $client->primary_phone)
                            No contact details provided
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Next steps --}}
<tr>
    <td class="email-padding" style="padding: 18px 32px 6px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 10px;">
            What happens next
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
            <tr>
                <td width="28" style="vertical-align: top; padding: 5px 0;">
                    <span style="display: inline-block; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700;">1</span>
                </td>
                <td style="font-size: 13px; color: #475569; padding: 5px 0 5px 6px; line-height: 1.6;">
                    Debits will be processed from the authorised account in line with issued invoices.
                </td>
            </tr>
            <tr>
                <td width="28" style="vertical-align: top; padding: 5px 0;">
                    <span style="display: inline-block; width: 22px; height: 22px; line-height: 22px; text-align: center; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700;">2</span>
                </td>
                <td style="font-size: 13px; color: #475569; padding: 5px 0 5px 6px; line-height: 1.6;">
                    To change the mandate, update the client's payment method before the next billing run.
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- CTA --}}
<tr>
    <td class="email-padding" align="center" style="padding: 22px 32px 28px;">
        <a href="{{ route('clients.show', $client) }}"
           style="display: inline-block; background: #16a34a; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 13px 34px; border-radius: 8px;">
            View Client
        </a>
    </td>
</tr>
@endsection

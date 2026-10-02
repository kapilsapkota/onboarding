@extends('emails.layout', [
    'logoUrl' => asset('images/allinit.png'),
    'brandName' => config('app.name'),
    'bandColor' => '#16a34a',
    'eyebrow' => 'NEW CLIENT',
    'emailTitle' => 'Onboarded Successfully',
    'subtitle' => 'A new client has joined '.config('app.name').'.',
    'badge' => '&#10003; ACTIVE',
    'preheader' => 'New client onboarded: '.($client->company_name ?? 'Unknown Company').' — details inside.',
    'footerBrand' => config('app.name'),
])

@section('emailBody')
@php
    $websites = $client->website ? json_decode($client->website, true) : [];
    $emails = $client->contacts->pluck('email')->filter();
    $phones = $client->contacts->pluck('phone')->filter();
@endphp

{{-- Intro --}}
<tr>
    <td class="email-padding" style="padding: 26px 32px 6px; font-size: 14px; line-height: 1.6; color: #374151;">
        Hi team,<br><br>
        <strong style="color: #111827;">{{ $client->company_name ?? 'A new client' }}</strong>
        has been successfully added. The details are below for your records.
    </td>
</tr>

{{-- Company spotlight --}}
<tr>
    <td class="email-padding" style="padding: 14px 32px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;">
            <tr>
                <td style="padding: 20px 22px; text-align: center;">
                    <div style="font-size: 20px; font-weight: 700; color: #111827;">
                        {{ $client->company_name ?? '-' }}
                    </div>
                    <div style="font-size: 13px; color: #475569; margin-top: 4px;">
                        {{ $client->industry ?? 'Industry not specified' }}
                    </div>
                    <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                        {{ $client->address ?? '-' }}, {{ $client->city ?? '-' }}, {{ $client->country ?? '-' }}
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- Client details --}}
<tr>
    <td class="email-padding" style="padding: 14px 32px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px;">
            Client Details
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; width: 42%; vertical-align: top;">
                    Website
                </td>
                <td style="padding: 8px 0; font-size: 13px; color: #111827; word-break: break-all;">
                    {{ ! empty($websites) ? implode(', ', $websites) : '-' }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                    Total contacts
                </td>
                <td style="padding: 8px 0; font-size: 13px; font-weight: 700; color: #111827;">
                    {{ $client->contacts->count() }}
                </td>
            </tr>
            @if($emails->isNotEmpty())
                <tr>
                    <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                        Emails
                    </td>
                    <td style="padding: 8px 0; font-size: 13px; color: #111827; word-break: break-all;">
                        {{ $emails->implode(', ') }}
                    </td>
                </tr>
            @endif
            @if($phones->isNotEmpty())
                <tr>
                    <td colspan="2" style="border-top: 1px solid #f1f5f9; font-size: 0; line-height: 0;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280; font-size: 13px; vertical-align: top;">
                        Phones
                    </td>
                    <td style="padding: 8px 0; font-size: 13px; color: #111827;">
                        {{ $phones->implode(', ') }}
                    </td>
                </tr>
            @endif
        </table>
    </td>
</tr>

@if($client->pasted_employees)
<tr>
    <td class="email-padding" style="padding: 10px 32px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 8px;">
            Employees
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <tr>
                <td style="padding: 16px 18px; font-size: 13px; color: #374151; line-height: 1.6; white-space: pre-line;">{{ $client->pasted_employees }}</td>
            </tr>
        </table>
    </td>
</tr>
@endif

@if($client->notes)
<tr>
    <td class="email-padding" style="padding: 10px 32px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 8px;">
            Notes
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <tr>
                <td style="padding: 16px 18px; font-size: 13px; color: #374151; line-height: 1.6; white-space: pre-line;">{{ $client->notes }}</td>
            </tr>
        </table>
    </td>
</tr>
@endif

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

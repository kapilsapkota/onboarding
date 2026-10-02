@extends('emails.layout', [
    'logoUrl' => asset('images/allinit.png'),
    'brandName' => config('app.name'),
    'bandColor' => '#4338ca',
    'eyebrow' => 'QUOTE '.($quoteNumber ?? ''),
    'emailTitle' => 'Your Quote is Ready',
    'subtitle' => isset($quoteTotal) ? 'Total: $'.number_format((float) $quoteTotal, 2) : null,
    'preheader' => 'Your quote '.($quoteNumber ?? '').' from '.config('app.name').' is ready — details inside.',
    'footerBrand' => 'Ali Taufeek | Growth & Strategy Director',
    'footerNote' => 'All in IT Solutions · Unit 3, 7-29 Bridge Rd, Stanmore NSW 2048 · allinit.solutions',
])

@section('emailBody')
{{-- Intro --}}
<tr>
    <td class="email-padding" style="padding: 26px 32px 6px; font-size: 14px; line-height: 1.6; color: #374151;">
        Hi {{ $clientName ?? 'there' }},<br><br>
        Thank you for choosing All in IT Solutions — please find your quote attached as discussed.
    </td>
</tr>

@if(! empty($quoteExpiry))
<tr>
    <td class="email-padding" style="padding: 10px 32px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px;">
            <tr>
                <td style="padding: 14px 18px; font-size: 13px; color: #3730a3;">
                    This quote is valid until <strong>{{ $quoteExpiry instanceof \DateTimeInterface ? $quoteExpiry->format('d M Y') : $quoteExpiry }}</strong>.
                </td>
            </tr>
        </table>
    </td>
</tr>
@endif

@if(! empty($extraMessage))
<tr>
    <td class="email-padding" style="padding: 10px 32px;">
        <div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 8px;">
            A note from our team
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
               style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <tr>
                <td style="padding: 16px 18px; font-size: 13px; color: #374151; line-height: 1.6; white-space: pre-line;">{{ $extraMessage }}</td>
            </tr>
        </table>
    </td>
</tr>
@endif

@if(! empty($publicUrl))
<tr>
    <td class="email-padding" align="center" style="padding: 22px 32px 8px;">
        <a href="{{ $publicUrl }}"
           style="display: inline-block; background: #4338ca; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 13px 34px; border-radius: 8px;">
            Review Quote &amp; Sign Online
        </a>
    </td>
</tr>
@endif

{{-- Sign-off --}}
<tr>
    <td class="email-padding" style="padding: 18px 32px 28px; font-size: 14px; line-height: 1.6; color: #374151;">
        Feel free to call me anytime to discuss this further.<br><br>
        Kind regards,<br>
        <strong style="color: #111827;">Ali Taufeek</strong> | Growth &amp; Strategy Director<br>
        <span style="font-size: 13px; color: #64748b;">allinit.solutions · Unit 3, 7-29 Bridge Rd, Stanmore NSW 2048</span>
    </td>
</tr>
@endsection

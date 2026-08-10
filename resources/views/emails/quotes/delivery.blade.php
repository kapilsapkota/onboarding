<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <title>{{ $quoteNumber }} - Quotation from AIIT</title>
    <style>
        /* Reset */
        * { box-sizing: border-box; }
        body, html { margin: 0; padding: 0; width: 100% !important; }
        body {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
            Helvetica, Arial, sans-serif;
        }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        td { vertical-align: top; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { text-decoration: none; }

        /* Responsive */
        @media only screen and (max-width: 620px) {
            .email-wrapper { padding: 16px !important; }
            .email-body { padding: 24px 20px !important; }
            .stat-table td { display: block !important; width: 100% !important; padding: 6px 0 !important; }
            .cta-button { display: block !important; text-align: center !important; }
            .items-table th, .items-table td { padding: 10px 8px !important; font-size: 13px !important; }
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f3f4f6;">

{{-- Preheader (hidden preview text) --}}
<div style="display:none;font-size:1px;color:#f3f4f6;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
    Your quotation {{ $quoteNumber }} from AIIT is ready for review.
    @if($quoteTotal)
        Total: ${{ number_format($quoteTotal, 2) }}.
    @endif
</div>

{{-- Outer wrapper --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;">
    <tr>
        <td class="email-wrapper" align="center" style="padding:32px 16px;">

            {{-- ================================================================
                 Container - max 600px
            ================================================================= --}}
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:600px;width:100%;">

                {{-- ─────────────────────────────────────────────────────────
                     HEADER
                ──────────────────────────────────────────────────────────── --}}
                <tr> <td style="padding-bottom:0;"> <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border:1px solid #e5e7eb; border-bottom:none;border-radius:12px 12px 0 0;"> <tr> <td style="padding:28px 32px 24px 32px;"> <table role="presentation" width="100%" cellpadding="0" cellspacing="0"> <tr> {{-- Company identity --}} <td style="vertical-align:middle;"> {{-- Replace with your real logo if available --}} @if(!empty($companyLogo)) <img src="{{ $companyLogo }}" alt="{{ $companyName ?? 'Company Logo' }}" style="display:block;max-width:150px;max-height:55px;"> @else <p style="margin:0;font-size:22px;font-weight:700; color:#111827;letter-spacing:-0.5px;"> {{ $companyName ?? 'YOUR COMPANY NAME' }} </p> @endif <p style="margin:5px 0 0;font-size:13px;color:#6b7280;"> {{ $companyTagline ?? 'Professional IT & Technology Solutions' }} </p> {{-- Company contact details --}} <p style="margin:8px 0 0;font-size:11px;color:#9ca3af;line-height:1.5;"> {{ $companyWebsite ?? 'www.yourcompany.com.au' }} @if(!empty($companyPhone)) &nbsp;&nbsp;•&nbsp;&nbsp; {{ $companyPhone }} @endif </p> </td> {{-- Quotation information --}} <td align="right" style="vertical-align:middle;width:150px;"> <p style="margin:0 0 5px;font-size:11px;font-weight:600; color:#9ca3af;text-transform:uppercase; letter-spacing:1px;"> Quotation </p> <p style="margin:0;font-size:16px;font-weight:700; color:#111827;"> {{ $quoteNumber }} </p> @if(!empty($quoteDate)) <p style="margin:5px 0 0;font-size:11px;color:#6b7280;"> {{ $quoteDate->format('d M Y') }} </p> @endif </td> </tr> </table> </td> </tr> {{-- Brand accent --}} <tr> <td style="padding:0;"> <table role="presentation" width="100%" cellpadding="0" cellspacing="0"> <tr> <td style="height:4px;background-color:#2563eb; font-size:0;line-height:0;"> &nbsp; </td> </tr> </table> </td> </tr> </table> </td> </tr>

                {{-- ─────────────────────────────────────────────────────────
                     BODY
                ──────────────────────────────────────────────────────────── --}}
                <tr>
                    <td>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="background-color:#ffffff;">
                            <tr>
                                <td class="email-body" style="padding:36px 32px;">

                                    {{-- Greeting --}}
                                    <p style="margin:0 0 20px;font-size:16px;font-weight:600;
                                              color:#111827;line-height:1.5;">
                                        Hi {{ $clientName }},
                                    </p>

                                    <p style="margin:0 0 20px;font-size:15px;color:#374151;line-height:1.7;">
                                        Thank you for the opportunity to work with you.
                                        Please find your quotation <strong style="color:#111827;">{{ $quoteNumber }}</strong>
                                        attached to this email as a PDF.
                                    </p>

                                    {{-- Extra message block --}}
                                    @if($extraMessage)
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                               style="margin-bottom:24px;">
                                            <tr>
                                                <td style="background-color:#f8fafc;border-left:3px solid #3b82f6;
                                                       border-radius:0 8px 8px 0;padding:16px 20px;">
                                                    <p style="margin:0;font-size:14px;color:#374151;
                                                          line-height:1.7;white-space:pre-line;">{{ $extraMessage }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    {{-- ──────────────────────────────────────
                                         Quote stats
                                    ─────────────────────────────────────── --}}
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                           class="stat-table"
                                           style="margin-bottom:28px;background-color:#f8fafc;
                                                  border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                                        <tr>
                                            <td style="padding:16px 20px;border-right:1px solid #e2e8f0;width:33.33%;">
                                                <p style="margin:0 0 4px;font-size:11px;font-weight:600;
                                                          color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                                                    Quote Number
                                                </p>
                                                <p style="margin:0;font-size:15px;font-weight:700;color:#111827;">
                                                    {{ $quoteNumber }}
                                                </p>
                                            </td>
                                            <td style="padding:16px 20px;border-right:1px solid #e2e8f0;width:33.33%;">
                                                <p style="margin:0 0 4px;font-size:11px;font-weight:600;
                                                          color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                                                    Total (inc. GST)
                                                </p>
                                                <p style="margin:0;font-size:15px;font-weight:700;color:#111827;">
                                                    ${{ number_format($quoteTotal ?? 0, 2) }}
                                                </p>
                                            </td>
                                            <td style="padding:16px 20px;width:33.33%;">
                                                <p style="margin:0 0 4px;font-size:11px;font-weight:600;
                                                          color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
                                                    Valid Until
                                                </p>
                                                <p style="margin:0;font-size:15px;font-weight:700;color:#111827;">
                                                    @if($quoteExpiry)
                                                        {{ $quoteExpiry->format('d M Y') }}
                                                    @else
                                                        -
                                                    @endif
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    {{-- ──────────────────────────────────────
                                         Items summary
                                    ─────────────────────────────────────── --}}
                                    @if($quote->items->isNotEmpty())
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                               style="margin-bottom:28px;">
                                            <tr>
                                                <td>
                                                    <p style="margin:0 0 12px;font-size:13px;font-weight:600;
                                                          color:#374151;text-transform:uppercase;letter-spacing:0.5px;">
                                                        Summary
                                                    </p>
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                           class="items-table"
                                                           style="border:1px solid #e2e8f0;border-radius:8px;
                                                              overflow:hidden;font-size:14px;">
                                                        {{-- Header --}}
                                                        <tr style="background-color:#f1f5f9;">
                                                            <th style="padding:10px 14px;text-align:left;font-size:11px;
                                                                   font-weight:600;color:#6b7280;
                                                                   text-transform:uppercase;letter-spacing:0.5px;">
                                                                Description
                                                            </th>
                                                            <th style="padding:10px 14px;text-align:right;font-size:11px;
                                                                   font-weight:600;color:#6b7280;
                                                                   text-transform:uppercase;letter-spacing:0.5px;
                                                                   white-space:nowrap;">
                                                                Total
                                                            </th>
                                                        </tr>
                                                        {{-- Items --}}
                                                        @foreach($quote->items as $item)
                                                            <tr style="border-top:1px solid #e2e8f0;">
                                                                <td style="padding:10px 14px;color:#374151;">
                                                                    {{ $item->product_name }}
                                                                    @if($item->quantity > 1)
                                                                        <span style="color:#9ca3af;font-size:12px;">
                                                                    &times; {{ $item->quantity }}
                                                                </span>
                                                                    @endif
                                                                </td>
                                                                <td style="padding:10px 14px;text-align:right;
                                                                   color:#111827;font-weight:600;white-space:nowrap;">
                                                                    ${{ number_format(($item->unit_price + $item->setup_fee), 2) }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        {{-- Total row --}}
                                                        <tr style="border-top:2px solid #e2e8f0;background-color:#f8fafc;">
                                                            <td style="padding:12px 14px;font-weight:700;color:#111827;">
                                                                Total (inc. GST)
                                                            </td>
                                                            <td style="padding:12px 14px;text-align:right;
                                                                   font-weight:700;color:#111827;font-size:15px;
                                                                   white-space:nowrap;">
                                                                ${{ number_format($quoteTotal ?? 0, 2) }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    {{-- ──────────────────────────────────────
                                         CTA button - only when public URL exists
                                    ─────────────────────────────────────── --}}
                                    @if($publicUrl)
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                               style="margin-bottom:28px;">
                                            <tr>
                                                <td align="center">
                                                    <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.6;">
                                                        You can also view your quote online using the button below:
                                                    </p>
                                                    <a href="{{ $publicUrl }}"
                                                       class="cta-button"
                                                       style="display:inline-block;background-color:#2563eb;
                                                          color:#ffffff;font-size:15px;font-weight:600;
                                                          text-decoration:none;padding:14px 32px;
                                                          border-radius:8px;letter-spacing:0.2px;">
                                                        View Quote Online
                                                    </a>
                                                    <p style="margin:12px 0 0;font-size:12px;color:#9ca3af;">
                                                        Or copy this link:
                                                        <a href="{{ $publicUrl }}"
                                                           style="color:#6b7280;word-break:break-all;">
                                                            {{ $publicUrl }}
                                                        </a>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    @endif

                                    {{-- ──────────────────────────────────────
                                         Closing
                                    ─────────────────────────────────────── --}}
                                    <p style="margin:0 0 8px;font-size:15px;color:#374151;line-height:1.7;">
                                        Please don't hesitate to reach out if you have any questions
                                        about this quote.
                                    </p>
                                    <p style="margin:0 0 28px;font-size:15px;color:#374151;line-height:1.7;">
                                        We look forward to working with you.
                                    </p>

                                    {{-- Signature --}}
                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="border-top:1px solid #e5e7eb;padding-top:20px;">
                                                <p style="margin:0 0 2px;font-size:14px;font-weight:700;color:#111827;">
                                                    The AIIT Team
                                                </p>
                                                <p style="margin:0;font-size:13px;color:#6b7280;">
                                                    AI &amp; IT Solutions
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- ─────────────────────────────────────────────────────────
                     FOOTER
                ──────────────────────────────────────────────────────────── --}}
                <tr>
                    <td>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="background-color:#f8fafc;border:1px solid #e2e8f0;
                                      border-top:none;border-radius:0 0 12px 12px;">
                            <tr>
                                <td style="padding:20px 32px;text-align:center;">

                                    {{-- Expiry notice --}}
                                    @if($quoteExpiry)
                                        <p style="margin:0 0 8px;font-size:12px;color:#6b7280;">
                                            This quotation is valid until
                                            <strong style="color:#374151;">{{ $quoteExpiry->format('d M Y') }}</strong>.
                                            After this date, prices may change.
                                        </p>
                                    @endif

                                    <p style="margin:0 0 8px;font-size:12px;color:#9ca3af;">
                                        This email and any attachments are confidential and intended solely
                                        for the addressee. If you have received this in error please notify us.
                                    </p>

                                    <p style="margin:0;font-size:12px;color:#9ca3af;">
                                        &copy; {{ date('Y') }} AIIT. All rights reserved.
                                    </p>

                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>
            {{-- /Container --}}

        </td>
    </tr>
</table>

</body>
</html>

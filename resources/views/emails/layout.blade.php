<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $emailTitle ?? config('app.name') }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; }
            .email-padding { padding-left: 20px !important; padding-right: 20px !important; }
            .email-title { font-size: 22px !important; }
            .stack-column { display: block !important; width: 100% !important; }
            .stack-column-right { text-align: left !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; word-spacing: normal; background: #f1f5f9;">
@if(! empty($preheader))
<div style="display: none; max-height: 0; overflow: hidden; opacity: 0;">
    {{ $preheader }}
</div>
@endif

<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation"
       style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; background: #f1f5f9; padding: 30px 12px;">
    <tr>
        <td align="center">

            <table class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" role="presentation"
                   style="max-width: 600px; width: 100%; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">

                {{-- Logo --}}
                <tr>
                    <td align="center" class="email-padding" style="background: #ffffff; padding: 26px 32px 22px;">
                        @if(! empty($logoUrl))
                            <img
                                src="{{ $logoUrl }}"
                                alt="{{ $brandName ?? config('app.name') }}"
                                style="display: block; max-width: 190px; max-height: 64px; width: auto; height: auto;"
                            >
                        @else
                            <div style="font-size: 18px; font-weight: 700; color: #111827;">
                                {{ $brandName ?? config('app.name') }}
                            </div>
                        @endif
                    </td>
                </tr>

                {{-- Header band --}}
                <tr>
                    <td class="email-padding" style="background: {{ $bandColor ?? '#16a34a' }}; padding: 30px 32px; color: #ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
                            <tr>
                                <td class="stack-column" style="vertical-align: middle;">
                                    @if(! empty($eyebrow))
                                        <div style="font-size: 12px; letter-spacing: 2px; font-weight: 700; opacity: 0.85; margin-bottom: 8px;">
                                            {{ $eyebrow }}
                                        </div>
                                    @endif
                                    <div class="email-title" style="font-size: 26px; font-weight: 700; line-height: 1.25;">
                                        {{ $emailTitle ?? '' }}
                                    </div>
                                    @if(! empty($subtitle))
                                        <div style="font-size: 14px; margin-top: 8px; opacity: 0.9; line-height: 1.5;">
                                            {{ $subtitle }}
                                        </div>
                                    @endif
                                </td>
                                @if(! empty($badge))
                                    <td class="stack-column stack-column-right" align="right" style="vertical-align: middle; padding-top: 12px;">
                                        <span style="
                                            display: inline-block;
                                            padding: 6px 14px;
                                            border-radius: 999px;
                                            background: #ffffff;
                                            color: {{ $bandColor ?? '#16a34a' }};
                                            font-size: 12px;
                                            font-weight: 700;
                                            letter-spacing: 1px;
                                        ">
                                            {!! $badge !!}
                                        </span>
                                    </td>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>

                @yield('emailBody')

                {{-- Footer --}}
                <tr>
                    <td class="email-padding" style="border-top: 1px solid #e5e7eb; padding: 20px 32px; background: #f9fafb; border-radius: 0 0 12px 12px;">
                        <div style="font-size: 13px; font-weight: 700; color: #374151;">
                            {{ $footerBrand ?? ($brandName ?? config('app.name')) }}
                        </div>
                        <div style="font-size: 12px; color: #9ca3af; margin-top: 6px; line-height: 1.6;">
                            {{ $footerNote ?? 'This is an automated message — please do not reply directly to this email.' }}
                        </div>
                    </td>
                </tr>

            </table>

            <div style="font-size: 11px; color: #94a3b8; margin-top: 16px; text-align: center;">
                Sent {{ now()->format('d M Y H:i') }}
            </div>

        </td>
    </tr>
</table>
</body>
</html>

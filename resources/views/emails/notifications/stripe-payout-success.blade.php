<table width="600" cellpadding="0" cellspacing="0" border="0"
       style="max-width: 600px; width: 100%; background: #ffffff; border-radius: 10px; overflow: hidden;">

    {{-- Logo --}}
    <tr>
        <td align="center" style="background: #ffffff; padding: 24px 32px 20px;">
            <img
                src="{{ asset('images/allinit.png') }}"
                alt="{{ config('app.name') }}"
                style="display: block; max-width: 180px; max-height: 60px; width: auto; height: auto;"
            >
        </td>
    </tr>

    {{-- Header --}}
    <tr>
        <td style="background: #16a34a; padding: 28px 32px; color: #ffffff;">
            <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">
                STRIPE PAYOUT
            </div>

            <div style="font-size: 24px; font-weight: 700;">
                Payout Successful
            </div>

            <div style="font-size: 14px; margin-top: 8px; opacity: 0.9;">
                The payout has been successfully processed by Stripe.
            </div>
        </td>
    </tr>

    {{-- Amount --}}
    <tr>
        <td style="padding: 30px 32px 20px; text-align: center;">
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
        <td style="padding: 10px 32px 20px;">
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
        <td style="padding: 10px 32px 20px;">
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
            </table>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="border-top: 1px solid #e5e7eb; padding: 20px 32px; background: #f9fafb;">
            <div style="font-size: 12px; color: #6b7280;">
                Processed at
            </div>

            <div style="font-size: 13px; color: #374151; margin-top: 4px;">
                {{ now()->format('d/m/Y H:i:s') }}
            </div>

            <div style="font-size: 12px; color: #9ca3af; margin-top: 16px;">
                This is an automated Stripe payout notification.
            </div>
        </td>
    </tr>

</table>

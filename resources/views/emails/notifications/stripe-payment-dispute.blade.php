<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; background: #f8fafc; padding: 30px 0;">
    <tr>
        <td align="center">

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
                    <td style="background: #d97706; padding: 28px 32px; color: #ffffff;">
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">
                            STRIPE PAYMENT
                        </div>

                        <div style="font-size: 24px; font-weight: 700;">
                            Payment Disputed
                        </div>

                        <div style="font-size: 14px; margin-top: 8px; opacity: 0.95;">
                            A payment has been disputed and may require your attention.
                        </div>
                    </td>
                </tr>

                {{-- Amount --}}
                <tr>
                    <td style="padding: 30px 32px 20px; text-align: center;">
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
                    <td style="padding: 10px 32px 20px;">
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
                    <td style="padding: 10px 32px 20px;">
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
                    <td style="padding: 10px 32px 20px;">
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
                    <td style="padding: 10px 32px 20px;">
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
                        </table>
                    </td>
                </tr>

                {{-- Original Payment --}}
                <tr>
                    <td style="padding: 10px 32px 20px;">
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
                    <td style="padding: 10px 32px 20px;">
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

                {{-- Footer --}}
                <tr>
                    <td style="border-top: 1px solid #e5e7eb; padding: 20px 32px; background: #f9fafb;">
                        <div style="font-size: 12px; color: #6b7280;">
                            Dispute received at
                        </div>

                        <div style="font-size: 13px; color: #374151; margin-top: 4px;">
                            {{ $createdAt }}
                        </div>

                        <div style="font-size: 12px; color: #9ca3af; margin-top: 16px;">
                            This is an automated Stripe payment notification.
                        </div>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

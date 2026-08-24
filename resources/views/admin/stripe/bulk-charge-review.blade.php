<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Review Bulk Charge
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto">

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                    <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment Method</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php $grandTotal = 0; @endphp

                    @foreach ($items as $item)
                        @php
                            $customer = \App\Models\StripeCustomer::find($item['stripe_customer_id']);
                            $pm       = \App\Models\StripePaymentMethod::find($item['stripe_payment_method_id']);
                            $grandTotal += $item['amount'];
                        @endphp
                        <tr>
                            <td class="px-5 py-4 font-medium">{{ $customer->name ?? $customer->email ?? '-' }}</td>
                            <td class="px-5 py-4 font-mono text-sm">{{ $pm->maskedLabel() }}</td>
                            <td class="px-5 py-4 text-right font-medium">
                                ${{ number_format($item['amount'] / 100, 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                    <tfoot class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <td colspan="2" class="px-5 py-4 font-semibold text-right">
                            {{ count($items) }} customer(s) - Total:
                        </td>
                        <td class="px-5 py-4 text-right font-bold text-lg">
                            ${{ number_format($grandTotal / 100, 2) }}
                        </td>
                    </tr>
                    </tfoot>

                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.stripe.bulk-charge.confirm') }}" class="mt-6">
            @csrf

            {{-- Pass validated items as hidden fields --}}
            @foreach ($items as $i => $item)
                <input type="hidden" name="items[{{ $i }}][stripe_customer_id]"       value="{{ $item['stripe_customer_id'] }}">
                <input type="hidden" name="items[{{ $i }}][stripe_payment_method_id]" value="{{ $item['stripe_payment_method_id'] }}">
                <input type="hidden" name="items[{{ $i }}][amount]"                   value="{{ $item['amount'] }}">
            @endforeach

            <div class="flex justify-between items-center">
                <a
                    href="{{ route('admin.stripe.bulk-charge') }}"
                    class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200"
                >
                    Back
                </a>

                <button
                    type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700"
                >
                    Confirm & Create Charges
                </button>
            </div>
        </form>

    </div>

</x-app-layout>

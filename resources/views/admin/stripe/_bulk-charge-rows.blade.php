@forelse ($customers as $index => $customer)
    @php $pm = $customer->paymentMethods->first(); @endphp

    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition"
        data-customer-id="{{ $customer->id }}"
        data-pm-id="{{ $pm->id }}"
        data-name="{{ $customer->name ?? $customer->email ?? 'Unknown' }}"
        data-pm="{{ $pm->maskedLabel() }}"
    >
        <td class="px-5 py-4">
            <input
                type="checkbox"
                class="customer-checkbox rounded"
            >
        </td>
        <td class="px-5 py-4 font-medium">{{ $customer->name ?? '-' }}</td>
        <td class="px-5 py-4 text-sm text-gray-500">{{ $customer->email ?? '-' }}</td>
        <td class="px-5 py-4 font-mono text-sm">{{ $pm->maskedLabel() }}</td>
        <td class="px-5 py-4">
            <input
                type="number"
                min="0.01"
                step="0.01"
                placeholder="0.00"
                class="amount-input w-32 px-3 py-1.5 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
            >
        </td>
        <td class="px-5 py-4">
            <input
                type="text"
                maxlength="255"
                placeholder="e.g. Invoice #1234"
                class="description-input w-56 px-3 py-1.5 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600"
            >
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="py-10 text-center text-gray-500">
            No eligible BECS customers found.
        </td>
    </tr>
@endforelse

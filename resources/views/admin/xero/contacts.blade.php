<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Xero Contact Reconciliation
        </h2>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- HEADER ACTIONS --}}
        <div class="flex items-center justify-between mb-4">

            <div>
                <h3 class="text-lg font-semibold">
                    {{ $xeroTenant->tenant_name }}
                </h3>
                <p class="text-sm text-gray-500">
                    Match Xero contacts with your internal customers
                </p>
            </div>

            <div class="flex gap-2">

                <button id="bulkMatchBtn"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Bulk Match Selected
                </button>

                <a href="{{ route('admin.xero.index') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    Back
                </a>

            </div>

        </div>

        {{-- TABLE --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 dark:bg-gray-700 text-left">
                <tr>
                    <th class="p-3">
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th class="p-3">Xero Contact</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Phone</th>
                    <th class="p-3">Match Customer</th>
                    <th class="p-3">Confidence</th>
                    <th class="p-3">Action</th>
                </tr>
                </thead>

                <tbody>

                @foreach($contacts as $contact)

                    @php
                        $bestMatch = collect($customers)->first(); // placeholder for now
                    @endphp

                    <tr class="border-t dark:border-gray-700">

                        {{-- SELECT --}}
                        <td class="p-3">
                            <input type="checkbox" class="contact-checkbox" value="{{ $contact['ContactID'] }}">
                        </td>

                        {{-- CONTACT --}}
                        <td class="p-3 font-medium">
                            {{ $contact['Name'] ?? '-' }}
                        </td>

                        {{-- EMAIL --}}
                        <td class="p-3 text-gray-500">
                            {{ $contact['EmailAddress'] ?? '-' }}
                        </td>

                        {{-- PHONE --}}
                        <td class="p-3 text-gray-500">
                            {{ $contact['Phones'][0]['PhoneNumber'] ?? '-' }}
                        </td>

                        {{-- MATCH DROPDOWN --}}
                        <td class="p-3">

                            <select class="w-full border rounded p-1 customer-select"
                                     data-contact="{{ $contact['ContactID'] }}">

                                <option value="">-- Select Customer --</option>

                                @foreach($customers as $customer)

                                    <option value="{{ $customer->id }}"
                                            @if(isset($contact['match']['customer']) && $contact['match']['customer']?->id === $customer->id)
                                                selected
                                        @endif
                                    >
                                        {{ $customer->company_name }}
                                    </option>

                                @endforeach

                            </select>

                        </td>

                        {{-- CONFIDENCE --}}
                        <td class="p-3">
                            @php $score = $contact['match']['score'] ?? 0; @endphp

                            @if($score >= 95)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Auto Match</span>
                            @elseif($score >= 60)
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">Possible Match</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">No Match</span>
                            @endif
                        </td>

                        {{-- ACTION --}}
                        <td class="p-3">

                            <button class="assign-btn px-3 py-1 text-xs bg-blue-600 text-white rounded"
                                    data-contact="{{ $contact['ContactID'] }}">
                                Assign
                            </button>

                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>
    </div>

    {{-- JS --}}
    <script>
        // Select all
        document.getElementById('selectAll').addEventListener('change', function () {
            document.querySelectorAll('.contact-checkbox')
                .forEach(cb => cb.checked = this.checked);
        });

        // Individual assign
        document.querySelectorAll('.assign-btn').forEach(btn => {
            btn.addEventListener('click', function () {

                let contactId = this.dataset.contact;
                let select = document.querySelector(`[data-contact="${contactId}"]`);
                let customerId = select.value;

                if (!customerId) {
                    alert('Please select a customer first');
                    return;
                }

                fetch('/admin/xero/assign-contact', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        contact_id: contactId,
                        customer_id: customerId,
                        tenant_id: '{{ $xeroTenant->id }}'
                    })
                }).then(res => res.json())
                    .then(data => {
                        alert('Assigned successfully');
                    });

            });
        });

        // Bulk assign
        document.getElementById('bulkMatchBtn').addEventListener('click', function () {

            let selected = [];

            document.querySelectorAll('.contact-checkbox:checked').forEach(cb => {
                selected.push(cb.value);
            });

            if (selected.length === 0) {
                alert('No contacts selected');
                return;
            }

            fetch('/admin/xero/bulk-assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    contacts: selected,
                    tenant_id: '{{ $xeroTenant->id }}'
                })
            }).then(res => res.json())
                .then(data => {
                    alert('Bulk assignment completed');
                });

        });
    </script>
</x-app-layout>

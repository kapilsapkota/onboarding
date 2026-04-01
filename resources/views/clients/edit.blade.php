<x-app-layout>
    @section('title', 'Edit — ' . ($client->company_name ?? 'Client'))

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                Edit - {{ $client->company_name ?? 'Client' }}
            </h2>
        </div>
    </x-slot>

    @php
        $input = 'w-full rounded-lg border border-gray-200 dark:border-gray-600 px-3 py-2 text-sm';
        $label = 'block text-xs font-semibold text-gray-500 uppercase mb-1';
        $card  = 'bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6';
    @endphp

    <div class="py-6 max-w-7xl mx-auto px-6">
        <form method="POST" action="{{ route('clients.update', $client) }}">
            @csrf
            @method('PUT')

            {{-- =========================
                Company Info
            ========================== --}}
            <div class="{{ $card }}">
                <div class="p-5 space-y-4">

                    <div>
                        <label class="{{ $label }}">Company Name</label>
                        <input class="{{ $input }}" name="company_name"
                               value="{{ old('company_name', $client->company_name) }}">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $label }}">Industry</label>
                            <input class="{{ $input }}" name="industry"
                                   value="{{ old('industry', $client->industry) }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Website</label>
                            <input class="{{ $input }}" name="website"
                                   value="{{ old('website', $client->website) }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $label }}">Address</label>
                            <input class="{{ $input }}" name="address" placeholder="Address"
                                   value="{{ old('address', $client->address) }}">
                        </div>

                       <div>
                           <label class="{{ $label }}">City</label>
                           <input class="{{ $input }}" name="city" placeholder="City"
                                  value="{{ old('city', $client->city) }}">
                       </div>

                        <div>
                            <label class="{{ $label }}">Post Code</label>
                            <input class="{{ $input }}" name="post_code" placeholder="Post Code"
                                   value="{{ old('post_code', $client->post_code) }}">
                        </div>

                        <div>
                            <label class="{{ $label }}">Country</label>
                            <input class="{{ $input }}" name="country" placeholder="Country"
                                   value="{{ old('country', $client->country) }}">
                        </div>
                    </div>

                </div>
            </div>

            {{-- =========================
                MAIN CONTACT
            ========================== --}}
            <div class="{{ $card }}">
                <div class="p-5">
                    <h3 class="font-semibold mb-4">Main Contact</h3>

                    <input type="hidden" name="contacts[0][contact_type]" value="Main Contact">

                    <div class="grid grid-cols-2 gap-4">
                        <input class="{{ $input }}" name="contacts[0][full_name]"
                               placeholder="Name"
                               value="{{ old('contacts.0.full_name', $mainContact->full_name ?? '') }}">

                        <input class="{{ $input }}" name="contacts[0][email]"
                               placeholder="Email"
                               value="{{ old('contacts.0.email', $mainContact->email ?? '') }}">

                        <input class="{{ $input }}" name="contacts[0][phone]"
                               placeholder="Phone"
                               value="{{ old('contacts.0.phone', $mainContact->phone ?? '') }}">

                        <input class="{{ $input }}" name="contacts[0][role]"
                               placeholder="Role"
                               value="{{ old('contacts.0.role', $mainContact->role ?? '') }}">
                    </div>
                </div>
            </div>

            {{-- =========================
                FINANCE
            ========================== --}}
            <div class="{{ $card }}">
                <div class="p-5">
                    <h3 class="font-semibold mb-4">Accounts Payable</h3>

                    <input type="hidden" name="contacts[1][contact_type]" value="Finance">

                    <div class="grid grid-cols-2 gap-4">
                        <input class="{{ $input }}" name="contacts[1][full_name]"
                               placeholder="Name"
                               value="{{ old('contacts.1.full_name', $financeContact->full_name ?? '') }}">

                        <input class="{{ $input }}" name="contacts[1][email]"
                               placeholder="Email"
                               value="{{ old('contacts.1.email', $financeContact->email ?? '') }}">

                        <input class="{{ $input }}" name="contacts[1][phone]"
                               placeholder="Phone"
                               value="{{ old('contacts.1.phone', $financeContact->phone ?? '') }}">
                    </div>
                </div>
            </div>

            {{-- =========================
                TECH
            ========================== --}}
            <div class="{{ $card }}">
                <div class="p-5">
                    <h3 class="font-semibold mb-4">Tech Contact</h3>

                    <input type="hidden" name="contacts[2][contact_type]" value="Technical">

                    <div class="grid grid-cols-2 gap-4">
                        <input class="{{ $input }}" name="contacts[2][full_name]"
                               placeholder="Name"
                               value="{{ old('contacts.2.full_name', $techContact->full_name ?? '') }}">

                        <input class="{{ $input }}" name="contacts[2][email]"
                               placeholder="Email"
                               value="{{ old('contacts.2.email', $techContact->email ?? '') }}">

                        <input class="{{ $input }}" name="contacts[2][phone]"
                               placeholder="Phone"
                               value="{{ old('contacts.2.phone', $techContact->phone ?? '') }}">
                    </div>
                </div>
            </div>

            {{-- =========================
                EMPLOYEES
            ========================== --}}
            <div class="{{ $card }}">
                <div class="p-5">
                    <h3 class="font-semibold mb-4">Employees</h3>

                    <div id="employees">
                        @foreach($employees as $i => $emp)
                            <div class="grid grid-cols-3 gap-3 mb-3 employee-row">
                                <input class="{{ $input }}" name="employees[{{ $i }}][name]"
                                       value="{{ $emp->full_name }}" placeholder="Name">

                                <input class="{{ $input }}" name="employees[{{ $i }}][email]"
                                       value="{{ $emp->email }}" placeholder="Email">

                                <input class="{{ $input }}" name="employees[{{ $i }}][phone]"
                                       value="{{ $emp->phone }}" placeholder="Phone">
                            </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addEmployee()" class="text-sm text-yellow-600 mt-2">
                        + Add Employee
                    </button>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end">
                <button class="bg-yellow-500 text-white px-6 py-2 rounded-lg">
                    Save
                </button>
            </div>

        </form>
    </div>

    <script>
        let empIndex = {{ count($employees) }};

        function addEmployee() {
            let row = `
                <div class="grid grid-cols-3 gap-3 mb-3">
                    <input name="employees[${empIndex}][name]" class="border p-2 rounded" placeholder="Name">
                    <input name="employees[${empIndex}][email]" class="border p-2 rounded" placeholder="Email">
                    <input name="employees[${empIndex}][phone]" class="border p-2 rounded" placeholder="Phone">
                </div>
            `;
            document.getElementById('employees').insertAdjacentHTML('beforeend', row);
            empIndex++;
        }
    </script>

</x-app-layout>

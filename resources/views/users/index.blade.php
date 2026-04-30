<x-app-layout>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .dataTables_filter {
            width: 50% !important;
        }
        .dataTables_filter input {
            width: 100% !important;
            max-width: 100% !important;
            padding: 8px 12px!important;
            border-radius: 8px!important;
            border: 1px solid #d1d5db!important;
        }

        .dataTables_length {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .dataTables_length label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dataTables_length select {
            flex-shrink: 0;
            width: auto;
            min-width: 70px;
        }
    </style>
    @section('title', 'Users')

    <div class="py-6">
        <div class="max-w-[95%] mx-auto">

            {{-- Header --}}
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Users
                </h2>

                <button onclick="openCreateModal()"
                        class="px-4 py-2 bg-gray-900 text-white rounded-md text-xs">
                    + Add User
                </button>
            </div>

            {{-- TABLE --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto px-2 py-3">

                    <table id="users-table" class="min-w-full">
                        <thead class="bg-gray-200 dark:bg-gray-700 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-center">Name</th>
                            <th class="px-4 py-3 text-center">Email</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($users as $user)
                            <tr class="border-b dark:border-gray-700">

                                <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-gray-100">
                                    {{ $user->name }}
                                </td>

                                <td class="px-4 py-3 text-sm text-center text-gray-500">
                                    {{ $user->email }}
                                </td>

                                <td class="px-4 py-3 text-center">

                                    <button
                                        onclick='openEditModal(@json($user))'
                                        class="px-3 py-1 text-xs bg-yellow-500 text-white rounded">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.users.destroy', $user) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button class="px-3 py-1 text-xs bg-red-600 text-white rounded">
                                            Delete
                                        </button>
                                    </form>

                                </td>

                            </tr>
                        @endforeach
                        </tbody>

                    </table>

                </div>
            </div>

        </div>
    </div>

    {{-- MODAL --}}
    <div x-data="userModal()"
         x-show="open"
         x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

        <div class="bg-white dark:bg-gray-800 w-full max-w-md p-6 rounded-lg">

            <h2 class="text-lg font-semibold mb-4"
                x-text="edit ? 'Edit User' : 'Create User'"></h2>

            <form :action="edit ? '/admin/users/' + user.id : '/admin/users'" method="POST">

                @csrf
                <template x-if="edit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-3">

                    <input type="text"
                           name="name"
                           x-model="user.name"
                           placeholder="Name"
                           class="w-full border rounded px-3 py-2">

                    <input type="email"
                           name="email"
                           x-model="user.email"
                           placeholder="Email"
                           class="w-full border rounded px-3 py-2">

                    <input type="password"
                           name="password"
                           placeholder="Password"
                           class="w-full border rounded px-3 py-2">

                </div>

                <div class="flex justify-end gap-2 mt-4">

                    <button type="button"
                            @click="open = false"
                            class="px-4 py-2 text-sm bg-gray-200 rounded">
                        Cancel
                    </button>

                    <button class="px-4 py-2 text-sm bg-yellow-500 text-white rounded">
                        Save
                    </button>

                </div>

            </form>
        </div>
    </div>

    {{-- JS --}}
    <script>
        function userModal() {
            return {
                open: false,
                edit: false,
                user: {},

                init() {
                    window.openCreateModal = () => {
                        this.edit = false;
                        this.user = {};
                        this.open = true;
                    };

                    window.openEditModal = (user) => {
                        this.edit = true;
                        this.user = user;
                        this.open = true;
                    };
                }
            }
        }
    </script>

    {{-- DataTables --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        $(document).ready(function () {
            if ($.fn.DataTable.isDataTable('#users-table')) {
                $('#users-table').DataTable().destroy();
                localStorage.removeItem('DataTables_users-table_/admin/users');

            }

            $.fn.dataTable.ext.errMode = 'none';

            $('#users-table').DataTable({
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                scrollY: '50vh',
                scrollX: true,
                lengthChange: true,
                paging: true,
                stateSave: true,
                dom:
                    '<"flex flex-wrap items-center justify-center mb-4"f>' +
                    '<"flex flex-wrap items-center justify-between gap-3 mb-4"' +
                    '<"flex items-center gap-2"l>' +
                    '<"flex items-center gap-2"B>' +
                    '>' +
                    'ti',
                buttons: [
                    {
                        extend: 'copy',
                        text: `<span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>Copy</span>`,
                        className: 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-md bg-blue-600 text-white hover:bg-blue-500'
                    },
                    {
                        extend: 'csv',
                        text: `<span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 11V3m0 8l-3-3m3 3l3-3M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2h-3l-3 3-3-3H7a2 2 0 00-2 2v5a2 2 0 002 2z"/>
            </svg>CSV</span>`,
                        className: 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-md bg-gray-900 text-white hover:bg-gray-700'
                    },
                    {
                        extend: 'excel',
                        text: `<span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m1 8H8a2 2 0 01-2-2V7a2 2 0 012-2h8a2 2 0 012 2v9a2 2 0 01-2 2z"/>
            </svg>Excel</span>`,
                        className: 'inline-flex items-center px-3 py-2 text-xs font-semibold rounded-md bg-yellow-500 text-white hover:bg-yellow-600'
                    }
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Quick Filter..",
                    lengthMenu: "Showing _MENU_ users per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ users",
                    infoEmpty: "No users available",
                    infoFiltered: "(filtered from _MAX_ total users)",
                    zeroRecords: "No matching users found",
                    emptyTable: `
            <div class="text-center py-10">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>

                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    No users found
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                   adjust your filters.
                </p>
            </div>
        `,
                    loadingRecords: "Loading...",
                    processing: "Processing...",

                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    },

                    buttons: {
                        csv: "Export CSV",
                        excel: "Export Excel"
                    }
                }
            });
        });
    </script>

</x-app-layout>

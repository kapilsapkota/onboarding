<x-app-layout>
    @section('title', 'Users')

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6m-12 6a3 3 0 100-6"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        Users
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Manage team members, roles and company access
                    </p>
                </div>
            </div>

            @can('create-user')
            <button onclick="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add User
            </button>
            @endcan
        </div>
    </x-slot>

    <x-message/>

    @php
        $totalUsers = $users->count();
        $adminCount = $users->filter(fn ($u) => $u->hasRole(['super-admin', 'admin']))->count();
        $managerCount = $users->filter(fn ($u) => $u->hasRole('manager'))->count();
        $linkedCompanies = $users->flatMap->companies->unique('id')->count();

        $roleBadge = function ($role) {
            return match ($role) {
                'super-admin' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                'admin' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                'manager' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                'user' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            };
        };
    @endphp

    <div class="space-y-5 pb-10">
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6m-12 6a3 3 0 100-6"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalUsers) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Administrators</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($adminCount) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Managers</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($managerCount) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Companies Linked</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($linkedCompanies) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($errors->any())
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="overflow-x-auto px-4 py-4">
                        <table id="users-table" class="min-w-full">
                            <thead>
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">Avatar</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Companies</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($users as $user)
                                @php
                                    $roleName = $user->roles->first()?->name ?? '—';
                                    $roleLabel = $user->roles->first()?->display_name ?: $roleName;
                                    $companyIds = $user->companies->pluck('id')->values();
                                @endphp
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">
                                        @if($user->avatar_url)
                                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                                 class="h-10 w-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700">
                                        @else
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 ring-2 ring-gray-100 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-700">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->phone ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $roleBadge($roleName) }}">
                                            {{ $roleLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($user->companies->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-1">
                                                @foreach($user->companies->take(2) as $company)
                                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                        {{ $company->name }}
                                                    </span>
                                                @endforeach
                                                @if($user->companies->count() > 2)
                                                    <span class="text-xs text-gray-400">+{{ $user->companies->count() - 2 }} more</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('edit-user')
                                            @php
                                                $editPayload = [
                                                    'id' => $user->id,
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                    'phone' => $user->phone,
                                                    'role' => $user->roles->first()?->name ?? '',
                                                    'companies' => $companyIds,
                                                    'avatar_url' => $user->avatar_url,
                                                ];
                                            @endphp
                                            <button onclick='openEditModal(@json($editPayload))'
                                                    title="Edit user"
                                                    class="inline-flex items-center justify-center rounded-lg border border-transparent bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span class="ml-1">Edit</span>
                                            </button>
                                            @endcan

                                            @can('delete-user')
                                            <x-delete-modal-button
                                                :model="$user"
                                                :action="route('admin.users.destroy', $user)"
                                                title="Delete User"
                                                :display_name="$user->name">Delete</x-delete-modal-button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- User modal --}}
    <div x-data="userModal()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-[2px]" @click="open = false"></div>

        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white"
                        x-text="edit ? 'Edit user' : 'Add user'"></h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                       x-text="edit ? 'Update details, role and company access.' : 'Create a new team member account.'"></p>
                </div>
                <button type="button" @click="open = false"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="edit ? '/admin/users/' + user.id : '/admin/users'" method="POST" enctype="multipart/form-data">
                @csrf
                <template x-if="edit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="max-h-[65vh] space-y-5 overflow-y-auto px-6 py-5">
                    <div class="flex items-center gap-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <img x-show="avatarPreview" :src="avatarPreview" alt="Avatar preview"
                             class="h-16 w-16 rounded-full object-cover ring-2 ring-white dark:ring-gray-600">
                        <div x-show="!avatarPreview" class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-200 text-xs text-gray-500 dark:bg-gray-600 dark:text-gray-300">
                            No photo
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Profile photo</label>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">JPG, PNG or WebP. Resized to 400×400.</p>
                            <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                   @change="previewAvatar($event)"
                                   class="mt-2 block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-gray-700 dark:text-gray-400 dark:file:bg-white dark:file:text-gray-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Full name</label>
                            <input type="text" name="name" x-model="user.name" placeholder="Jane Smith" required
                                   class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" x-model="user.email" placeholder="jane@example.com" required
                                   class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input type="text" name="phone" x-model="user.phone" placeholder="0400 000 000"
                                   class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <select name="role" x-model="user.role"
                                    class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">— Select role —</option>
                                @foreach($roles as $roleOption)
                                    <option value="{{ $roleOption->name }}">{{ $roleOption->display_name ?: $roleOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Companies</label>
                            <span class="text-xs text-gray-400" x-text="(user.companies || []).length + ' selected'"></span>
                        </div>
                        <div class="relative">
                            <input type="text" placeholder="Search companies..." x-model="companySearch"
                                   class="block w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <div class="mt-2 max-h-36 space-y-0.5 overflow-y-auto rounded-lg border border-gray-200 p-1.5 dark:border-gray-600">
                            @foreach($companies as $company)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700"
                                       x-show="'{{ strtolower($company->name) }}'.includes(companySearch.toLowerCase())">
                                    <input type="checkbox" name="companies[]" value="{{ $company->id }}"
                                           :checked="(user.companies || []).map(String).includes('{{ $company->id }}')"
                                           class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    {{ $company->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <input type="password" name="password"
                               :placeholder="edit ? 'Leave blank to keep unchanged' : 'Minimum 6 characters'"
                               :required="!edit"
                               class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/30">
                    <button type="button" @click="open = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Save user
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function userModal() {
            return {
                open: false,
                edit: false,
                user: {},
                avatarPreview: null,
                companySearch: '',

                init() {
                    window.openCreateModal = () => {
                        this.edit = false;
                        this.user = { companies: [] };
                        this.avatarPreview = null;
                        this.companySearch = '';
                        this.open = true;
                    };

                    window.openEditModal = (user) => {
                        this.edit = true;
                        this.user = {
                            id: user.id,
                            name: user.name,
                            email: user.email,
                            phone: user.phone || '',
                            role: user.role || '',
                            companies: (user.companies || []).map(String),
                        };
                        this.avatarPreview = user.avatar_url || null;
                        this.companySearch = '';
                        this.open = true;
                    };
                },

                previewAvatar(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => { this.avatarPreview = e.target.result; };
                    reader.readAsDataURL(file);
                }
            }
        }
    </script>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        #users-table.dataTable { border-collapse: collapse !important; }
        #users-table thead th { border-bottom: 1px solid #e5e7eb !important; }
        .dark #users-table thead th { border-bottom-color: #374151 !important; }
        .dataTables_wrapper .dataTables_filter { float: none !important; text-align: left !important; }
        .dataTables_wrapper .dataTables_filter label { display: flex; align-items: center; gap: .5rem; font-size: .75rem; color: #6b7280; }
        .dataTables_wrapper .dataTables_filter input {
            flex: 1; max-width: 22rem; margin-left: 0 !important;
            padding: .55rem .9rem .55rem 2.25rem !important;
            border-radius: .65rem !important; border: 1px solid #e5e7eb !important;
            font-size: .8rem !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-size: 1rem; background-position: .7rem center;
        }
        .dark .dataTables_wrapper .dataTables_filter input { background-color: #374151 !important; border-color: #4b5563 !important; color: #f3f4f6 !important; }
        .dataTables_wrapper .dataTables_length { font-size: .75rem; color: #6b7280; }
        .dataTables_wrapper .dataTables_length select {
            border-radius: .5rem !important; border: 1px solid #e5e7eb !important;
            padding: .3rem .5rem !important; font-size: .75rem !important;
        }
        .dark .dataTables_wrapper .dataTables_length select { background-color: #374151 !important; border-color: #4b5563 !important; color: #f3f4f6 !important; }
        .dataTables_wrapper .dataTables_info { font-size: .75rem !important; color: #6b7280 !important; padding-top: 1rem !important; }
        .dataTables_wrapper .dataTables_paginate { padding-top: 1rem !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: .5rem !important; border: 1px solid transparent !important;
            font-size: .75rem !important; padding: .35rem .75rem !important; color: #374151 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #111827 !important; color: #fff !important; border-color: #111827 !important;
        }
        .dark .dataTables_wrapper .dataTables_info, .dark .dataTables_wrapper .dataTables_paginate .paginate_button { color: #9ca3af !important; }
        .dark .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #f9fafb !important; color: #111827 !important; border-color: #f9fafb !important; }
    </style>
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
                scrollCollapse: true,
                lengthChange: true,
                paging: true,
                stateSave: true,
                columnDefs: [
                    { orderable: false, targets: [0, 6] }
                ],
                dom:
                    '<"flex flex-wrap items-center justify-between gap-3 mb-4"<"flex items-center"f><"flex items-center gap-2"B>>' +
                    '<"flex flex-wrap items-center justify-between gap-3 mb-3"<"flex items-center"l><"flex items-center"i>>' +
                    't' +
                    '<"flex flex-wrap items-center justify-end gap-3 mt-3"p>',
                buttons: [
                    {
                        extend: 'copy',
                        text: 'Copy',
                        className: 'inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50'
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        className: 'inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50'
                    },
                    {
                        extend: 'excel',
                        text: 'Excel',
                        className: 'inline-flex items-center rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-gray-700'
                    }
                ],
                language: {
                    search: "Search:",
                    searchPlaceholder: "Name, email, phone...",
                    lengthMenu: "Show _MENU_ per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ users",
                    infoEmpty: "No users available",
                    infoFiltered: "(filtered from _MAX_ total users)",
                    zeroRecords: "No matching users found",
                    emptyTable: `
            <div class="text-center py-10">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 2a3 3 0 100-6m-12 6a3 3 0 100-6"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No users found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Adjust your filters or add a new user.</p>
            </div>`,
                    loadingRecords: "Loading...",
                    processing: "Processing...",
                    paginate: { first: "First", last: "Last", next: "Next", previous: "Prev" },
                    buttons: { csv: "Export CSV", excel: "Export Excel" }
                }
            });
        });
    </script>

</x-app-layout>

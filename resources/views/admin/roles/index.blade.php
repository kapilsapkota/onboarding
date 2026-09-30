<x-app-layout>
    @section('title', 'Roles')

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        Roles
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Define roles and assign permissions per module
                    </p>
                </div>
            </div>

            @can('create-role')
            <button onclick="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Role
            </button>
            @endcan
        </div>
    </x-slot>

    <x-message/>

    @php
        $totalPermissions = $groupedPermissions->flatten()->count();
        $usersCovered = $roles->sum(fn ($r) => $r->users->count());
    @endphp

    <div class="space-y-5 pb-10">
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Roles</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($roles->count()) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 13a6 6 0 00-12 0"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Permissions</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPermissions) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Modules</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($groupedPermissions->keys()->count()) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Role Assignments</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($usersCovered) }}</p>
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
                        <table class="min-w-full">
                            <thead>
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Users</th>
                                <th class="px-4 py-3">Permissions</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($roles as $role)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $role->display_name ?: $role->name }}
                                        </div>
                                        <div class="mt-0.5 text-xs font-mono text-gray-400">{{ $role->name }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $role->users->count() }} {{ Str::plural('user', $role->users->count()) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($role->permissions->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-1">
                                                @foreach($role->permissions->take(4) as $permission)
                                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                                        {{ $permission->name }}
                                                    </span>
                                                @endforeach
                                                @if($role->permissions->count() > 4)
                                                    <span class="text-xs text-gray-400">+{{ $role->permissions->count() - 4 }} more</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">No permissions</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('edit-role')
                                            @php
                                                $editPayload = [
                                                    'id' => $role->id,
                                                    'name' => $role->name,
                                                    'display_name' => $role->display_name,
                                                    'permission_ids' => $role->permissions->pluck('id')->values(),
                                                ];
                                            @endphp
                                            <button onclick='openEditModal(@json($editPayload))'
                                                    title="Edit role"
                                                    class="inline-flex items-center justify-center rounded-lg border border-transparent bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span class="ml-1">Edit</span>
                                            </button>
                                            @endcan

                                            @can('delete-role')
                                            <x-delete-modal-button
                                                :model="$role"
                                                :action="route('admin.roles.destroy', $role)"
                                                title="Delete Role"
                                                :display_name="$role->display_name ?: $role->name">Delete</x-delete-modal-button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">No roles found</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create your first role to get started.</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Role modal --}}
    <div x-data="roleModal()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-[2px]" @click="open = false"></div>

        <div class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white"
                        x-text="edit ? 'Edit role' : 'Add role'"></h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Tick permissions per module, or bulk-select a whole module.</p>
                </div>
                <button type="button" @click="open = false"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="edit ? '/admin/roles/' + role.id : '/admin/roles'" method="POST" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <template x-if="edit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">System name <span class="text-gray-400">(slug)</span></label>
                            <input type="text" name="name" x-model="role.name" placeholder="e.g. manager" required
                                   :disabled="edit && role.name === 'super-admin'"
                                   class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Display name</label>
                            <input type="text" name="display_name" x-model="role.display_name" placeholder="e.g. Manager"
                                   class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Permissions · <span x-text="selectedCount()"></span> selected
                        </p>
                        <div class="flex gap-2">
                            <button type="button" @click="selectAll(true)"
                                    class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Select all</button>
                            <button type="button" @click="selectAll(false)"
                                    class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Clear</button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach($groupedPermissions as $group => $permissions)
                            @php $gid = \Illuminate\Support\Str::slug($group ?: 'other', '_'); @endphp
                            <div class="rounded-xl border border-gray-200 dark:border-gray-600" data-module-card="{{ $gid }}">
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-t-xl bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                                    <input type="checkbox" data-module-toggle="{{ $gid }}"
                                           @change="toggleModule('{{ $gid }}', $event.target.checked)"
                                           class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $group ?: 'Other' }}</span>
                                    <span class="ml-auto text-xs text-gray-400" data-module-count="{{ $gid }}"></span>
                                </label>
                                <div class="grid grid-cols-1 gap-0.5 p-2 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach($permissions as $permission)
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                   data-module="{{ $gid }}"
                                                   :checked="selected.includes('{{ $permission->id }}')"
                                                   @change="refreshModule('{{ $gid }}')"
                                                   class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                            <span class="font-mono text-xs">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/30">
                    <button type="button" @click="open = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Save role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function roleModal() {
            return {
                open: false,
                edit: false,
                role: { name: '', display_name: '', permission_ids: [] },
                selected: [],

                init() {
                    window.openCreateModal = () => {
                        this.edit = false;
                        this.role = { name: '', display_name: '', permission_ids: [] };
                        this.selected = [];
                        this.open = true;
                        this.$nextTick(() => this.refreshAll());
                    };

                    window.openEditModal = (role) => {
                        this.edit = true;
                        this.role = { id: role.id, name: role.name, display_name: role.display_name || '' };
                        this.selected = (role.permission_ids || []).map(String);
                        this.open = true;
                        this.$nextTick(() => this.refreshAll());
                    };
                },

                selectedCount() { return this.selected.length; },

                toggleModule(gid, checked) {
                    document.querySelectorAll('input[data-module="' + gid + '"]').forEach((box) => {
                        box.checked = checked;
                        this.syncSelected(box.value, checked);
                    });
                    this.refreshModule(gid);
                },

                selectAll(checked) {
                    document.querySelectorAll('input[data-module]').forEach((box) => {
                        box.checked = checked;
                    });
                    this.selected = checked
                        ? Array.from(document.querySelectorAll('input[data-module]')).map((b) => b.value)
                        : [];
                    this.refreshAll();
                },

                syncSelected(value, checked) {
                    value = String(value);
                    if (checked && !this.selected.includes(value)) this.selected.push(value);
                    if (!checked) this.selected = this.selected.filter((v) => v !== value);
                },

                refreshModule(gid) {
                    const boxes = Array.from(document.querySelectorAll('input[data-module="' + gid + '"]'));
                    boxes.forEach((box) => this.syncSelected(box.value, box.checked));
                    const checked = boxes.filter((b) => b.checked).length;
                    const toggle = document.querySelector('input[data-module-toggle="' + gid + '"]');
                    if (toggle) {
                        toggle.checked = boxes.length > 0 && checked === boxes.length;
                        toggle.indeterminate = checked > 0 && checked < boxes.length;
                    }
                    const counter = document.querySelector('[data-module-count="' + gid + '"]');
                    if (counter) counter.textContent = checked + '/' + boxes.length;
                },

                refreshAll() {
                    document.querySelectorAll('[data-module-card]').forEach((card) => {
                        const gid = card.getAttribute('data-module-card');
                        this.refreshModule(gid);
                    });
                }
            }
        }
    </script>

</x-app-layout>

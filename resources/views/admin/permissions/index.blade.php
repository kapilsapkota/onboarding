<x-app-layout>
    @section('title', 'Permissions')

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M15 7a3 3 0 012 2.94A3 3 0 0115 13H9v-2h6a1 1 0 000-2H9V7h6zM7 21a2 2 0 01-2-2v-2a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H7zm0-18a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        Permissions
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Manage access permissions grouped by module
                    </p>
                </div>
            </div>

            @can('create-permission')
            <button onclick="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Permission
            </button>
            @endcan
        </div>
    </x-slot>

    <x-message/>

    @php
        $totalPermissions = $groupedPermissions->flatten()->count();
        $unassigned = $groupedPermissions->flatten()->filter(fn ($p) => $p->roles->isEmpty())->count();
        $rolesCount = \Spatie\Permission\Models\Role::count();
    @endphp

    <div class="space-y-5 pb-10">
        <section>
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a3 3 0 012 2.94A3 3 0 0115 13H9v-2h6a1 1 0 000-2H9V7h6zM7 21a2 2 0 01-2-2v-2a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H7zm0-18a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"/>
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
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Roles Using</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($rolesCount) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Unassigned</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($unassigned) }}</p>
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

        <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-10">
            <div class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm
             dark:border-gray-700 dark:bg-gray-800">
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="perm-search" type="text" placeholder="Quick search permissions..."
                       oninput="filterPermissions(this.value)"
                       class="block w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm
                                    shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1
                                    focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                <span id="perm-search-count" class="shrink-0 text-xs text-gray-400"></span>
            </div>
        </div>

        {{-- Bulk action bar --}}
        <div id="bulk-bar" class="sticky top-20 z-20 mx-auto hidden max-w-full px-4 sm:px-6 lg:px-10">
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur dark:border-gray-600 dark:bg-gray-800/95">
                <span id="bulk-count" class="text-sm font-semibold text-gray-800 dark:text-gray-100">0 selected</span>

                @can('edit-permission')
                <form id="bulk-group-form" action="{{ route('admin.permissions.bulk-group') }}" method="POST"
                      onsubmit="return attachBulkIds(this)"
                      class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                    @csrf
                    <input type="text" name="group" list="bulk-group-list" placeholder="Move to module..." required
                           class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    <datalist id="bulk-group-list">
                        @foreach($groups as $existingGroup)
                            <option value="{{ $existingGroup }}"></option>
                        @endforeach
                    </datalist>
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Move
                    </button>
                </form>
                @endcan

                @can('delete-permission')
                <form id="bulk-delete-form" action="{{ route('admin.permissions.bulk-destroy') }}" method="POST"
                      onsubmit="return confirmBulkDelete(this)">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500">
                        Delete
                    </button>
                </form>
                @endcan

                <button type="button" onclick="clearBulkSelection()"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    Clear
                </button>
            </div>
        </div>

        <section>
            <div class="mx-auto max-w-full space-y-4 px-4 sm:px-6 lg:px-10">
                @forelse($groupedPermissions as $group => $permissions)
                    @php $gid = \Illuminate\Support\Str::slug($group ?: 'other', '_'); @endphp
                    <div data-module-card="{{ $gid }}" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-700/40">
                            <input type="checkbox" data-bulk-module="{{ $gid }}"
                                   onchange="toggleBulkModule('{{ $gid }}', this.checked)"
                                   title="Select all in {{ $group ?: 'Other' }}"
                                   class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $group ?: 'Other' }}</h3>
                            <span class="inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-600 dark:text-gray-200">
                                {{ $permissions->count() }}
                            </span>
                        </div>
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($permissions as $permission)
                                <li data-perm-name="{{ strtolower($permission->name) }}" class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <input type="checkbox" value="{{ $permission->id }}"
                                           data-bulk-item="{{ $gid }}" onchange="refreshBulkBar()"
                                           title="Select {{ $permission->name }}"
                                           class="h-4 w-4 shrink-0 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    <div class="min-w-0 flex-1">
                                        <span class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $permission->name }}</span>
                                        @if($permission->roles->isNotEmpty())
                                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                                @foreach($permission->roles->take(4) as $role)
                                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-px text-[11px] text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ $role->display_name ?: $role->name }}
                                                    </span>
                                                @endforeach
                                                @if($permission->roles->count() > 4)
                                                    <span class="text-[11px] text-gray-400">+{{ $permission->roles->count() - 4 }} more</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="mt-1 text-[11px] text-gray-400">Not assigned to any role</div>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        @can('edit-permission')
                                        @php
                                            $editPayload = [
                                                'id' => $permission->id,
                                                'name' => $permission->name,
                                                'group' => $permission->group,
                                            ];
                                        @endphp
                                        <button onclick='openEditModal(@json($editPayload))'
                                                title="Edit permission"
                                                class="inline-flex items-center justify-center rounded-lg border border-transparent bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span class="ml-1">Edit</span>
                                        </button>
                                        @endcan

                                        @can('delete-permission')
                                        <x-delete-modal-button
                                            :model="$permission"
                                            :action="route('admin.permissions.destroy', $permission)"
                                            title="Delete Permission"
                                            :display_name="$permission->name">Delete</x-delete-modal-button>
                                        @endcan
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="rounded-2xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">No permissions found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create your first permission to get started.</p>
                    </div>
                @endforelse
                <div id="perm-no-results" class="hidden rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-600 dark:bg-gray-800">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No permissions match your search</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Try a different keyword.</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Permission modal --}}
    <div x-data="permissionModal()" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-[2px]" @click="open = false"></div>

        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white"
                        x-text="edit ? 'Edit permission' : 'Add permission'"></h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Use lowercase dashes, e.g. export-report.</p>
                </div>
                <button type="button" @click="open = false"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="edit ? '/admin/permissions/' + permission.id : '/admin/permissions'" method="POST">
                @csrf
                <template x-if="edit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">System name</label>
                        <input type="text" name="name" x-model="permission.name" placeholder="e.g. export-report" required
                               class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 font-mono text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Module group</label>
                        <input type="text" name="group" x-model="permission.group" list="group-list" placeholder="e.g. Reports" required
                               class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        <datalist id="group-list">
                            @foreach($groups as $existingGroup)
                                <option value="{{ $existingGroup }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/30">
                    <button type="button" @click="open = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Save permission
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function permissionModal() {
            return {
                open: false,
                edit: false,
                permission: { name: '', group: '' },

                init() {
                    window.openCreateModal = () => {
                        this.edit = false;
                        this.permission = { name: '', group: '' };
                        this.open = true;
                    };

                    window.openEditModal = (permission) => {
                        this.edit = true;
                        this.permission = { id: permission.id, name: permission.name, group: permission.group || '' };
                        this.open = true;
                    };
                }
            }
        }

        function selectedBulkIds() {
            return Array.from(document.querySelectorAll('input[data-bulk-item]:checked')).map((box) => box.value);
        }

        function refreshBulkBar() {
            const ids = selectedBulkIds();
            document.getElementById('bulk-bar').classList.toggle('hidden', ids.length === 0);
            document.getElementById('bulk-count').textContent = ids.length + ' selected';

            document.querySelectorAll('input[data-bulk-module]').forEach((toggle) => {
                const gid = toggle.getAttribute('data-bulk-module');
                const boxes = Array.from(document.querySelectorAll('input[data-bulk-item="' + gid + '"]'));
                const checked = boxes.filter((box) => box.checked).length;
                toggle.checked = boxes.length > 0 && checked === boxes.length;
                toggle.indeterminate = checked > 0 && checked < boxes.length;
            });
        }

        function toggleBulkModule(gid, checked) {
            document.querySelectorAll('input[data-bulk-item="' + gid + '"]').forEach((box) => {
                box.checked = checked;
            });
            refreshBulkBar();
        }

        function clearBulkSelection() {
            document.querySelectorAll('input[data-bulk-item]:checked').forEach((box) => {
                box.checked = false;
            });
            refreshBulkBar();
        }

        function attachBulkIds(form) {
            form.querySelectorAll('input[name="ids[]"]').forEach((node) => node.remove());
            selectedBulkIds().forEach((id) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'ids[]';
                hidden.value = id;
                form.appendChild(hidden);
            });
            return true;
        }

        function confirmBulkDelete(form) {
            const count = selectedBulkIds().length;
            if (count === 0) return false;
            if (!confirm('Delete ' + count + ' selected permission(s)? They will be removed from all roles.')) return false;
            return attachBulkIds(form);
        }

        function filterPermissions(query) {
            query = query.trim().toLowerCase();
            let totalVisible = 0;
            let total = 0;
            document.querySelectorAll('[data-module-card]').forEach((card) => {
                let visible = 0;
                card.querySelectorAll('[data-perm-name]').forEach((row) => {
                    total++;
                    const match = row.getAttribute('data-perm-name').includes(query);
                    row.classList.toggle('hidden', !match);
                    if (match) visible++;
                });
                card.classList.toggle('hidden', visible === 0);
                totalVisible += visible;
            });
            document.getElementById('perm-no-results').classList.toggle('hidden', totalVisible > 0);
            document.getElementById('perm-search-count').textContent = query === '' ? '' : totalVisible + ' of ' + total;
        }
    </script>

</x-app-layout>

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorizeAction('view-role');

        $roles = Role::with(['permissions', 'users'])->orderBy('name')->get();
        $groupedPermissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy(fn (Permission $p) => $p->group ?: 'Other');

        return view('admin.roles.index', compact('roles', 'groupedPermissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Role created');
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();

        if ($role->name === 'super-admin' && $validated['name'] !== 'super-admin') {
            abort(403, 'The super-admin role cannot be renamed.');
        }

        $role->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'] ?? null,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Role updated');
    }

    public function destroy(Request $request, Role $role)
    {
        $this->authorizeAction('delete-role');

        if ($role->name === 'super-admin') {
            return back()->withErrors(['role' => 'The super-admin role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'This role is assigned to users and cannot be deleted.']);
        }

        $role->delete();

        return back()->with('success', 'Role deleted');
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorizeAction('view-permission');

        $groupedPermissions = Permission::with('roles')->orderBy('group')->orderBy('name')->get()
            ->groupBy(fn (Permission $p) => $p->group ?: 'Other');
        $groups = Permission::distinct()->orderBy('group')->pluck('group')->filter()->values();

        return view('admin.permissions.index', compact('groupedPermissions', 'groups'));
    }

    public function store(StorePermissionRequest $request)
    {
        $validated = $request->validated();

        Permission::create([
            'name' => $validated['name'],
            'group' => $validated['group'],
            'guard_name' => 'web',
        ]);

        return back()->with('success', 'Permission created');
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $validated = $request->validated();

        $permission->update([
            'name' => $validated['name'],
            'group' => $validated['group'],
        ]);

        return back()->with('success', 'Permission updated');
    }

    public function destroy(Request $request, Permission $permission)
    {
        $this->authorizeAction('delete-permission');

        $permission->delete();

        return back()->with('success', 'Permission deleted');
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}

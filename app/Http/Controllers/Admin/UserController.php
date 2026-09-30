<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorizeAction('view-user');

        $users = User::with(['roles', 'companies'])->latest()->get();
        $roles = Role::orderBy('name')->get(['name', 'display_name']);
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'roles', 'companies'));
    }

    public function store(StoreUserRequest $request, AvatarService $avatars)
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => bcrypt($validated['password']),
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $avatars->store($request->file('avatar'));
        }

        $user = User::create($data);

        $this->syncRole($request, $user, $validated['role'] ?? null);
        $user->companies()->sync($validated['companies'] ?? []);

        return back()->with('success', 'User created');
    }

    public function update(UpdateUserRequest $request, User $user, AvatarService $avatars)
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            $old = $user->avatar;
            $data['avatar'] = $avatars->store($request->file('avatar'));
            $user->update($data);
            $avatars->delete($old);
        } else {
            $user->update($data);
        }

        $this->syncRole($request, $user, $validated['role'] ?? null);
        $user->companies()->sync($validated['companies'] ?? []);

        return back()->with('success', 'User updated');
    }

    public function destroy(Request $request, User $user, AvatarService $avatars)
    {
        $this->authorizeAction('delete-user');

        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $avatars->delete($user->avatar);
        $user->companies()->detach();
        $user->delete();

        return back()->with('success', 'User deleted');
    }

    private function syncRole(Request $request, User $user, ?string $role): void
    {
        if ($role === null || $role === '') {
            return;
        }

        $roleModel = Role::where('name', $role)->firstOrFail();

        if ($roleModel->name === 'super-admin' && ! $request->user()->hasRole('super-admin')) {
            abort(403, 'Only super-admins can assign the super-admin role.');
        }

        $user->syncRoles([$roleModel->name]);
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}

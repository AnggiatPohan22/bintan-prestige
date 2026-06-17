<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->where('is_admin', true)
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%' . $request->string('search')->toString() . '%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('status'), function ($query) use ($request): void {
                if ($request->string('status')->toString() === 'active') {
                    $query->where('is_active', true);
                }

                if ($request->string('status')->toString() === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => User::ADMIN_ROLES,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => User::ADMIN_ROLES,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->is_admin = true;
        $user->role = $validated['role'];
        $user->is_active = $request->boolean('is_active');
        $user->created_by = $request->user()->id;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user created successfully.');
    }

    public function edit(User $user): View
    {
        $this->ensureAdminUser($user);

        return view('admin.users.edit', [
            'managedUser' => $user,
            'roles' => User::ADMIN_ROLES,
            'activeSuperAdminCount' => $this->activeSuperAdminCount(),
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureAdminUser($user);

        $validated = $request->validated();
        $nextRole = $validated['role'];
        $nextIsActive = $request->boolean('is_active');

        if ($request->user()->is($user) && ! $nextIsActive) {
            return back()
                ->withErrors(['is_active' => 'You cannot deactivate your own admin account.'])
                ->withInput();
        }

        if ($this->wouldRemoveLastActiveSuperAdmin($user, $nextRole, $nextIsActive)) {
            return back()
                ->withErrors(['role' => 'At least one active Super Admin must remain.'])
                ->withInput();
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $nextRole;
        $user->is_active = $nextIsActive;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'Admin user updated successfully.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdminUser($user);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot deactivate your own admin account.');
        }

        if ($this->wouldRemoveLastActiveSuperAdmin($user, $user->role, false)) {
            return back()->with('error', 'At least one active Super Admin must remain.');
        }

        $user->is_active = false;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user deactivated successfully.');
    }

    private function ensureAdminUser(User $user): void
    {
        abort_unless($user->is_admin, 404);
    }

    private function activeSuperAdminCount(): int
    {
        return User::query()
            ->where('is_admin', true)
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->where('is_active', true)
            ->count();
    }

    private function wouldRemoveLastActiveSuperAdmin(User $user, string $nextRole, bool $nextIsActive): bool
    {
        if (! $user->is_admin || $user->role !== User::ROLE_SUPER_ADMIN || ! $user->is_active) {
            return false;
        }

        if ($nextRole === User::ROLE_SUPER_ADMIN && $nextIsActive) {
            return false;
        }

        return $this->activeSuperAdminCount() <= 1;
    }
}

@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="admin-page-title">
                    Admin Users
                </h1>

                <p class="admin-page-subtitle">
                    Manage CMS admin accounts and access status.
                </p>
            </div>

            <a
                href="{{ route('admin.users.create') }}"
                class="admin-btn-primary w-full sm:w-auto"
            >
                Create Admin
            </a>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <form class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_180px_180px_auto]">
                <div>
                    <label for="admin-user-search" class="admin-form-label">Search</label>
                    <input
                        id="admin-user-search"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="admin-input"
                        placeholder="Name or email"
                    >
                </div>

                <div>
                    <label for="admin-user-role" class="admin-form-label">Role</label>
                    <select id="admin-user-role" name="role" class="admin-select">
                        <option value="">All roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected(request('role') === $role)>
                                {{ str($role)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="admin-user-status" class="admin-form-label">Status</label>
                    <select id="admin-user-status" name="status" class="admin-select">
                        <option value="">All status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="admin-btn-primary w-full px-4 py-3">
                        Filter
                    </button>

                    <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary px-4 py-3">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="admin-card-body">
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr class="admin-table-header">
                            <th class="px-4 py-4">User</th>
                            <th class="px-4 py-4">Role</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4">Created</th>
                            <th class="px-4 py-4 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $managedUser)
                            <tr class="admin-table-row">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-admin-primary">
                                        {{ $managedUser->name }}
                                    </div>
                                    <div class="mt-1 text-xs text-admin-secondary">
                                        {{ $managedUser->email }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="{{ $managedUser->isSuperAdmin() ? 'admin-badge-info' : 'admin-badge-success' }}">
                                        {{ str($managedUser->role)->replace('_', ' ')->title() }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span class="{{ $managedUser->is_active ? 'admin-badge-success' : 'admin-badge-warning' }}">
                                        {{ $managedUser->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm text-admin-secondary">
                                    {{ $managedUser->created_at?->format('d M Y H:i') }}
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-col justify-end gap-2 sm:flex-row">
                                        <a
                                            href="{{ route('admin.users.edit', $managedUser) }}"
                                            class="admin-btn-soft px-4 py-2"
                                        >
                                            Edit
                                        </a>

                                        @if(auth()->id() !== $managedUser->id && $managedUser->is_active)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.users.deactivate', $managedUser) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Deactivate this admin user?')"
                                                    class="admin-btn-danger px-4 py-2"
                                                >
                                                    Deactivate
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6">
                                    <div class="admin-empty-state">
                                        No admin users found.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

@endsection

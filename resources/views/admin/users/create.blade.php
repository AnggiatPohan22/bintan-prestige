@extends('layouts.admin')

@section('content')

<div class="admin-page">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Create Admin User</h1>
            <p class="admin-page-subtitle">
                Create a CMS account for a trusted admin.
            </p>
        </div>

        <a
            href="{{ route('admin.users.index') }}"
            class="admin-btn-secondary mt-5 w-full sm:mt-0 sm:w-auto"
        >
            Back
        </a>
    </div>

    <div class="admin-form-card">
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="admin-form-label">Name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="admin-input @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                        required
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="admin-form-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="admin-input @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                        required
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="password" class="admin-form-label">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="admin-input @error('password') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                        required
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="admin-form-label">Confirm Password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="admin-input"
                        required
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="role" class="admin-form-label">Role</label>
                    <select
                        id="role"
                        name="role"
                        class="admin-select @error('role') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                        required
                    >
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', \App\Models\User::ROLE_ADMIN) === $role)>
                                {{ str($role)->replace('_', ' ')->title() }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="rounded border-admin text-indigo-600 focus:ring-indigo-500"
                            @checked(old('is_active', true))
                        >
                        <span class="text-sm font-semibold text-admin-primary">
                            Active admin account
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-admin pt-5 sm:flex-row sm:items-center">
                <button type="submit" class="admin-btn-primary w-full sm:w-auto">
                    Create Admin
                </button>

                <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary w-full sm:w-auto">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

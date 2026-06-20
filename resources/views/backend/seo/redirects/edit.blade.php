@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Edit Redirect</h1>
            <p class="admin-page-subtitle font-mono text-sm">{{ $redirect->from_url }}</p>
        </div>
        <a href="{{ route('admin.seo.redirects.index') }}" class="admin-btn-secondary">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    <div class="admin-card max-w-xl">
        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.seo.redirects.update', $redirect) }}" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <label class="admin-form-label" for="from_url">From URL <span class="text-red-500">*</span></label>
                    <input id="from_url" name="from_url" type="text" class="admin-input w-full @error('from_url') border-red-400 @enderror"
                           value="{{ old('from_url', $redirect->from_url) }}" required>
                    @error('from_url')<p class="admin-form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-form-label" for="to_url">To URL <span class="text-red-500">*</span></label>
                    <input id="to_url" name="to_url" type="text" class="admin-input w-full @error('to_url') border-red-400 @enderror"
                           value="{{ old('to_url', $redirect->to_url) }}" required>
                    @error('to_url')<p class="admin-form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-form-label" for="status_code">Redirect Type</label>
                    <select id="status_code" name="status_code" class="admin-input w-full">
                        <option value="301" @selected(old('status_code', $redirect->status_code) == 301)>301 — Permanent</option>
                        <option value="302" @selected(old('status_code', $redirect->status_code) == 302)>302 — Temporary</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" name="is_active" type="checkbox" class="admin-checkbox" value="1"
                           @checked(old('is_active', $redirect->is_active))>
                    <label for="is_active" class="text-sm text-slate-700">Active</label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="admin-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

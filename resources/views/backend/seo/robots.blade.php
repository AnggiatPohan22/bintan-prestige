@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Robots.txt</h1>
            <p class="admin-page-subtitle">
                Edit the robots.txt file served at
                <a href="{{ url('/robots.txt') }}" target="_blank" class="text-indigo-600 hover:underline">/robots.txt</a>.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="admin-card max-w-2xl">
        <div class="admin-card-body">
            <form method="POST" action="{{ route('admin.seo.robots.update') }}">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label class="admin-form-label" for="content">robots.txt content</label>
                    <textarea
                        id="content"
                        name="content"
                        rows="16"
                        class="admin-input w-full font-mono text-sm @error('content') border-red-400 @enderror"
                        spellcheck="false"
                    >{{ old('content', $content) }}</textarea>
                    @error('content')<p class="admin-form-error">{{ $message }}</p>@enderror
                    <p class="admin-form-hint">
                        Changes are saved to <code>storage/app/seo/robots.txt</code> and served immediately at /robots.txt.
                    </p>
                </div>

                <button type="submit" class="admin-btn-primary">Save robots.txt</button>
            </form>
        </div>
    </div>
</div>
@endsection

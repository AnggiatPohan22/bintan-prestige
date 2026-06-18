@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Create Page</h1>
                <p class="admin-page-subtitle">
                    Create a new page for your website.
                </p>
            </div>

            <a
                href="{{ route('admin.pages.index') }}"
                class="admin-btn-secondary mt-5 w-full sm:mt-0 sm:w-auto"
            >
                Back
            </a>
        </div>

        <div class="admin-form-card">
            @include('backend.pages.form')
        </div>
    </div>
@endsection

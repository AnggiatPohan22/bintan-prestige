@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.forms.index') }}" class="text-admin-secondary hover:text-admin-secondary">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <h1 class="admin-page-title">Create Form</h1>
        </div>
    </div>

    @include('backend.contact-forms.partials.form-builder', ['form' => null])
</div>
@endsection

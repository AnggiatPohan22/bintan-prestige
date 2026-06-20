@extends('layouts.admin')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.forms.index') }}" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <h1 class="admin-page-title">Edit Form: {{ $form->name }}</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success mb-4">{{ session('success') }}</div>
    @endif

    @include('backend.contact-forms.partials.form-builder', ['form' => $form])
</div>
@endsection

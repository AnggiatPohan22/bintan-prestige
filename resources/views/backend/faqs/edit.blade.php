@extends('layouts.admin')

@section('content')

@include('backend.faqs.form', [
    'action' => route('admin.faqs.update', $faq),
    'method' => 'PUT',
    'title' => 'Edit FAQ',
])

@endsection

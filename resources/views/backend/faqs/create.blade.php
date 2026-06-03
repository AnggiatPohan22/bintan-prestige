@extends('layouts.admin')

@section('content')
@include('backend.faqs.form', ['action' => route('admin.faqs.store'), 'method' => 'POST', 'title' => 'Create FAQ'])
@endsection

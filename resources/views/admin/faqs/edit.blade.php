@extends('layouts.admin')

@section('content')
    @include('admin.faqs.form', ['faq' => $faq])
@endsection

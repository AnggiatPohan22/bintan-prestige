@extends('layouts.admin')

@section('content')
    @include('backend.products.form', [
        'mode' => 'edit'
    ])
@endsection
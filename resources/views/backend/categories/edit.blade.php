@extends('layouts.admin')

@section('content')
    @include('backend.categories.form', ['category' => $category])
@endsection

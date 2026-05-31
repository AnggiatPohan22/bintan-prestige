@extends('layouts.admin')

@section('content')
    @include('backend.destinations.form', ['destination' => $destination])
@endsection

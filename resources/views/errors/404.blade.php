@php
    // The exception handler bypasses route-group middleware, so SetLocale did not
    // run. Derive the locale straight from the URL segment so the 404 renders in
    // the visitor's language.
    app()->setLocale(\App\Support\Locales::localeFromRequest());
@endphp
@extends('frontend.frontend')

@section('content')
    <section class="mx-auto max-w-2xl px-6 py-20 text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-admin-secondary">404</p>
        <h1 class="mt-3 text-3xl font-bold text-slate-800 sm:text-4xl">
            {{ __('frontend.not_found_title') }}
        </h1>
        <p class="mt-4 text-base text-slate-600">
            {{ __('frontend.not_found_body') }}
        </p>
        <div class="mt-8">
            <a href="{{ url(\App\Support\Locales::current() === \App\Support\Locales::default() ? '/' : '/'.\App\Support\Locales::current()) }}"
               class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                {{ __('frontend.not_found_home_cta') }}
            </a>
        </div>
    </section>
@endsection

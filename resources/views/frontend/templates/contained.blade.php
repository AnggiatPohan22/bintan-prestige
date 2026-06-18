{{-- Template: Contained / Article — narrow centered column with the page title (text pages). --}}
<div class="cms-page min-h-screen">
    <div class="mx-auto max-w-3xl px-6 py-12">
        <header class="mb-8 border-b border-slate-200 pb-6">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                {{ $page->title }}
            </h1>
        </header>

        @include('frontend.pages._blocks', ['page' => $page])
    </div>
</div>

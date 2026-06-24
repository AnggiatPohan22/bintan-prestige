@extends('layouts.admin')

@section('content')

<div class="admin-page max-w-2xl">

    <div class="mb-6">
        <a href="{{ route('admin.themes.widgets.index', $theme) }}"
           class="mb-1 inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-400">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to Widgets
        </a>
        <h1 class="text-lg font-extrabold text-slate-100">Edit Widget — {{ $theme->name }}</h1>
        <p class="mt-0.5 text-sm text-slate-400">
            Type: <span class="font-medium">{{ $widget->typeLabel() }}</span>
            &middot;
            Area: <span class="font-medium">{{ $areas[$widget->area]['label'] ?? $widget->area }}</span>
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('admin.themes.widgets.update', [$theme, $widget]) }}"
    >
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">

            {{-- Admin Title --}}
            <div>
                <label for="title" class="admin-label">Admin Label</label>
                <input id="title" name="title" type="text"
                       value="{{ old('title', $widget->title) }}"
                       class="admin-input w-full" maxlength="255">
            </div>

            {{-- Sort Order --}}
            <div>
                <label for="sort_order" class="admin-label">Sort Order</label>
                <input id="sort_order" name="sort_order" type="number"
                       value="{{ old('sort_order', $widget->sort_order) }}"
                       class="admin-input w-32" min="0">
            </div>

            <hr class="border-slate-100">

            @php $data = $widget->data ?? []; @endphp

            @if($widget->widget_type === 'text')
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Text Widget</p>
                <div class="space-y-4">
                    <div>
                        <label class="admin-label">Heading</label>
                        <input name="data[heading]" type="text"
                               value="{{ old('data.heading', $data['heading'] ?? '') }}"
                               class="admin-input w-full" maxlength="255">
                    </div>
                    <div>
                        <label class="admin-label">Content</label>
                        <textarea name="data[content]" rows="8" class="admin-input w-full font-mono text-sm">{{ old('data.content', $data['content'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-slate-400">HTML is supported.</p>
                    </div>
                </div>
            @endif

            @if($widget->widget_type === 'html')
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">HTML Widget</p>
                <div>
                    <label class="admin-label">HTML Code</label>
                    <textarea name="data[code]" rows="10" class="admin-input w-full font-mono text-sm">{{ old('data.code', $data['code'] ?? '') }}</textarea>
                </div>
            @endif

            @if($widget->widget_type === 'image')
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Image Widget</p>
                <div class="space-y-4">
                    <div>
                        <label class="admin-label">Image URL</label>
                        <input name="data[src]" type="text"
                               value="{{ old('data.src', $data['src'] ?? '') }}"
                               class="admin-input w-full" maxlength="2048">
                    </div>
                    <div>
                        <label class="admin-label">Alt Text</label>
                        <input name="data[alt]" type="text"
                               value="{{ old('data.alt', $data['alt'] ?? '') }}"
                               class="admin-input w-full" maxlength="255">
                    </div>
                    <div>
                        <label class="admin-label">Link URL (optional)</label>
                        <input name="data[link_url]" type="text"
                               value="{{ old('data.link_url', $data['link_url'] ?? '') }}"
                               class="admin-input w-full" maxlength="2048">
                    </div>
                    <div>
                        <label class="admin-label">Caption (optional)</label>
                        <input name="data[caption]" type="text"
                               value="{{ old('data.caption', $data['caption'] ?? '') }}"
                               class="admin-input w-full" maxlength="500">
                    </div>
                </div>
            @endif

            @if($widget->widget_type === 'navigation')
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Navigation Widget</p>
                <div class="space-y-4" x-data="navLinks({{ json_encode($data['links'] ?? []) }})">
                    <div>
                        <label class="admin-label">Heading</label>
                        <input name="data[heading]" type="text"
                               value="{{ old('data.heading', $data['heading'] ?? '') }}"
                               class="admin-input w-full" maxlength="255">
                    </div>
                    <div>
                        <label class="admin-label">Links</label>
                        <div class="space-y-2">
                            <template x-for="(link, i) in links" :key="i">
                                <div class="flex items-center gap-2">
                                    <input type="text" :name="`data[links][${i}][label]`"
                                           x-model="link.label"
                                           class="admin-input flex-1" placeholder="Label">
                                    <input type="text" :name="`data[links][${i}][url]`"
                                           x-model="link.url"
                                           class="admin-input flex-1" placeholder="/page-slug">
                                    <button type="button" @click="remove(i)"
                                            class="shrink-0 text-red-400 hover:text-red-600">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="add()"
                                class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">
                            <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>
                            Add Link
                        </button>
                    </div>
                </div>
            @endif

        </div>

        <div class="mt-5 flex gap-3">
            <button type="submit" class="admin-btn-primary">
                <i class="fa-solid fa-floppy-disk mr-1.5" aria-hidden="true"></i>
                Update Widget
            </button>
            <a href="{{ route('admin.themes.widgets.index', $theme) }}" class="admin-btn-secondary">Cancel</a>
        </div>

    </form>
</div>

<script>
    function navLinks(existing) {
        return {
            links: existing.length ? existing : [{ label: '', url: '' }],
            add()     { this.links.push({ label: '', url: '' }); },
            remove(i) { this.links.splice(i, 1); },
        };
    }
</script>

@endsection

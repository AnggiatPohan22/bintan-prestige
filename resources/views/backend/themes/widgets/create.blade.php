@extends('layouts.admin')

@section('content')

<div class="admin-page max-w-2xl">

    <div class="mb-6">
        <a href="{{ route('admin.themes.widgets.index', $theme) }}"
           class="mb-1 inline-flex items-center gap-1 text-xs text-admin-secondary hover:text-admin-secondary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to Widgets
        </a>
        <h1 class="text-lg font-extrabold text-admin-secondary">Add Widget — {{ $theme->name }}</h1>
    </div>

    <form
        method="POST"
        action="{{ route('admin.themes.widgets.store', $theme) }}"
        x-data="widgetForm('{{ old('widget_type', 'text') }}')"
    >
        @csrf

        @if($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-admin bg-admin-card p-6 shadow-sm space-y-5">

            {{-- Widget Area --}}
            <div>
                <label for="area" class="admin-label">Widget Area <span class="text-red-500">*</span></label>
                <select id="area" name="area" class="admin-select w-full" required>
                    @foreach($areas as $key => $config)
                        <option value="{{ $key }}" {{ old('area', $preselArea) === $key ? 'selected' : '' }}>
                            {{ $config['label'] ?? ucwords(str_replace('-', ' ', $key)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Widget Type --}}
            <div>
                <label for="widget_type" class="admin-label">Widget Type <span class="text-red-500">*</span></label>
                <select id="widget_type" name="widget_type" class="admin-select w-full" x-model="type" required>
                    <option value="text">Text</option>
                    <option value="html">HTML</option>
                    <option value="image">Image</option>
                    <option value="navigation">Navigation</option>
                </select>
            </div>

            {{-- Admin Title --}}
            <div>
                <label for="title" class="admin-label">Admin Label</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}"
                       class="admin-input w-full" placeholder="e.g. Footer About Text"
                       maxlength="255">
                <p class="mt-1 text-xs text-admin-secondary">Optional label to identify this widget in the admin.</p>
            </div>

            {{-- Sort Order --}}
            <div>
                <label for="sort_order" class="admin-label">Sort Order</label>
                <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', 0) }}"
                       class="admin-input w-32" min="0">
            </div>

            <hr class="border-admin">

            {{-- TEXT fields --}}
            <div x-show="type === 'text'" x-cloak>
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-admin-secondary">Text Widget</p>
                <div class="space-y-4">
                    <div>
                        <label class="admin-label">Heading</label>
                        <input name="data[heading]" type="text" value="{{ old('data.heading') }}"
                               class="admin-input w-full" maxlength="255" placeholder="Optional heading">
                    </div>
                    <div>
                        <label class="admin-label">Content</label>
                        <textarea name="data[content]" rows="6" class="admin-input w-full font-mono text-sm"
                                  placeholder="HTML or plain text content">{{ old('data.content') }}</textarea>
                        <p class="mt-1 text-xs text-admin-secondary">HTML is supported.</p>
                    </div>
                </div>
            </div>

            {{-- HTML fields --}}
            <div x-show="type === 'html'" x-cloak>
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-admin-secondary">HTML Widget</p>
                <div>
                    <label class="admin-label">HTML Code</label>
                    <textarea name="data[code]" rows="8" class="admin-input w-full font-mono text-sm"
                              placeholder="<div>...</div>">{{ old('data.code') }}</textarea>
                </div>
            </div>

            {{-- IMAGE fields --}}
            <div x-show="type === 'image'" x-cloak>
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-admin-secondary">Image Widget</p>
                <div class="space-y-4">
                    <div>
                        <label class="admin-label">Image URL <span class="text-red-500">*</span></label>
                        <input name="data[src]" type="text" value="{{ old('data.src') }}"
                               class="admin-input w-full" maxlength="2048" placeholder="https://...">
                    </div>
                    <div>
                        <label class="admin-label">Alt Text</label>
                        <input name="data[alt]" type="text" value="{{ old('data.alt') }}"
                               class="admin-input w-full" maxlength="255">
                    </div>
                    <div>
                        <label class="admin-label">Link URL (optional)</label>
                        <input name="data[link_url]" type="text" value="{{ old('data.link_url') }}"
                               class="admin-input w-full" maxlength="2048" placeholder="https://...">
                    </div>
                    <div>
                        <label class="admin-label">Caption (optional)</label>
                        <input name="data[caption]" type="text" value="{{ old('data.caption') }}"
                               class="admin-input w-full" maxlength="500">
                    </div>
                </div>
            </div>

            {{-- NAVIGATION fields --}}
            <div x-show="type === 'navigation'" x-cloak x-data="navLinks({{ json_encode([]) }})">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-admin-secondary">Navigation Widget</p>
                <div class="space-y-4">
                    <div>
                        <label class="admin-label">Heading</label>
                        <input name="data[heading]" type="text" value="{{ old('data.heading') }}"
                               class="admin-input w-full" maxlength="255" placeholder="Optional heading">
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
                                           class="admin-input flex-1" placeholder="/page-slug or https://...">
                                    <button type="button" @click="remove(i)"
                                            class="shrink-0 text-red-400 hover:text-red-600"
                                            title="Remove link">
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
            </div>

        </div>

        <div class="mt-5 flex gap-3">
            <button type="submit" class="admin-btn-primary">
                <i class="fa-solid fa-floppy-disk mr-1.5" aria-hidden="true"></i>
                Save Widget
            </button>
            <a href="{{ route('admin.themes.widgets.index', $theme) }}" class="admin-btn-secondary">Cancel</a>
        </div>

    </form>
</div>

<script>
    function widgetForm(initial) {
        return { type: initial };
    }

    function navLinks(existing) {
        return {
            links: existing.length ? existing : [{ label: '', url: '' }],
            add()   { this.links.push({ label: '', url: '' }); },
            remove(i) { this.links.splice(i, 1); },
        };
    }
</script>

@endsection

{{--
    A single menu item row in the editor tree.
    Vars: $item (MenuItem), $typeMeta (array), $isChild (bool), $menu (from parent scope).
--}}
@php
    $meta = $typeMeta[$item->link_type] ?? ['label' => $item->link_type, 'icon' => 'fa-link'];
@endphp

<div class="flex items-center gap-2 {{ $isChild ? 'px-2.5 py-1.5' : 'px-3 py-2.5' }}">
    {{-- Drag handle --}}
    <button type="button" data-handle
            class="cursor-grab text-admin-secondary hover:text-admin-secondary active:cursor-grabbing" title="Drag to reorder">
        <i class="fa-solid fa-grip-vertical"></i>
    </button>

    {{-- Type icon --}}
    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-admin-card text-admin-secondary">
        <i class="fa-solid {{ $meta['icon'] }} text-xs"></i>
    </span>

    {{-- Label + resolved URL --}}
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-admin-secondary">
            {{ $item->label }}
            @unless($item->is_active)
                <span class="ml-1 rounded bg-admin-card px-1.5 py-0.5 text-[10px] font-medium text-admin-secondary">Hidden</span>
            @endunless
        </p>
        <p class="truncate text-xs text-admin-secondary">
            <span class="font-mono">{{ $meta['label'] }}</span>
            <span class="mx-1">·</span>{{ $item->resolveUrl() }}
            @if($item->target === '_blank')<i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[10px]"></i>@endif
        </p>
    </div>

    {{-- Actions --}}
    <div class="flex shrink-0 items-center gap-1">
        <button type="button"
                x-on:click="openEdit({
                    id: {{ $item->id }},
                    label: @js($item->label),
                    linkType: '{{ $item->link_type }}',
                    linkableId: '{{ $item->linkable_id }}',
                    url: @js($item->url),
                    newTab: {{ $item->target === '_blank' ? 'true' : 'false' }},
                    parentId: '{{ $item->parent_id }}'
                })"
                class="grid h-8 w-8 place-items-center rounded-lg text-admin-secondary hover:opacity-75" title="Edit">
            <i class="fa-solid fa-pen text-xs"></i>
        </button>

        <form method="POST" action="{{ route('admin.menu-items.toggle-active', [$menu, $item]) }}">
            @csrf
            <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg text-admin-secondary hover:opacity-75"
                    title="{{ $item->is_active ? 'Hide' : 'Show' }}">
                <i class="fa-solid {{ $item->is_active ? 'fa-eye' : 'fa-eye-slash' }} text-xs"></i>
            </button>
        </form>

        <form method="POST" action="{{ route('admin.menu-items.destroy', [$menu, $item]) }}">
            @csrf @method('DELETE')
            <button type="submit" data-confirm="Delete &quot;{{ $item->label }}&quot;{{ $item->children->isNotEmpty() ? ' and all its dropdown items' : '' }}?"
                    class="grid h-8 w-8 place-items-center rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600" title="Delete">
                <i class="fa-solid fa-trash text-xs"></i>
            </button>
        </form>
    </div>
</div>

@php
    /**
     * @var \Illuminate\Database\Eloquent\Collection<int, App\Models\Taxonomy> $taxonomies
     * @var list<int> $selectedTermIds
     */
    $selectedTermIds ??= [];
@endphp

@if($taxonomies->isNotEmpty())
    @foreach($taxonomies as $taxonomy)
        <div class="rounded-lg border border-slate-200 p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                    {{ $taxonomy->label_plural }}
                </h2>
                <a href="{{ route('admin.taxonomies.terms.create', $taxonomy) }}"
                   class="text-xs text-indigo-600 hover:underline" target="_blank">
                    + Add term
                </a>
            </div>

            @if($taxonomy->terms->isEmpty())
                <p class="text-sm text-admin-secondary italic">
                    No {{ strtolower($taxonomy->label_plural) }} yet.
                </p>
            @elseif($taxonomy->is_hierarchical)
                {{-- Hierarchical: indent children under parents --}}
                @foreach($taxonomy->terms->whereNull('parent_id') as $rootTerm)
                    <div class="space-y-1.5">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="terms[]" value="{{ $rootTerm->id }}"
                                   {{ in_array($rootTerm->id, $selectedTermIds) ? 'checked' : '' }}
                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-medium text-slate-700">{{ $rootTerm->name }}</span>
                        </label>
                        @foreach($taxonomy->terms->where('parent_id', $rootTerm->id) as $childTerm)
                            <label class="ml-6 flex cursor-pointer items-center gap-2">
                                <input type="checkbox" name="terms[]" value="{{ $childTerm->id }}"
                                       {{ in_array($childTerm->id, $selectedTermIds) ? 'checked' : '' }}
                                       class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-700">{{ $childTerm->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            @else
                {{-- Flat: tags-style checkboxes --}}
                <div class="flex flex-wrap gap-3">
                    @foreach($taxonomy->terms as $term)
                        <label class="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 px-3 py-1.5
                                      hover:border-indigo-300 hover:bg-indigo-50 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                            <input type="checkbox" name="terms[]" value="{{ $term->id }}"
                                   {{ in_array($term->id, $selectedTermIds) ? 'checked' : '' }}
                                   class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-700">{{ $term->name }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
@endif

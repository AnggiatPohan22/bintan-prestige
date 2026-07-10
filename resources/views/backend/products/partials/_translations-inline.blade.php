{{--
    Phase 7 (B6.1) — per-locale translation inputs for a product-child row's
    Edit modal. Passed:
      $record : the ProductHighlight/Feature/Faq/Itinerary/Note being edited
      $fields : ['field' => 'Label', ...] map of field key → visible label
      $textareaFields : list<string> subset of $fields that should render as textarea
--}}
@php
    $__locales = \App\Support\Locales::nonDefaultActive();
    $__textareaFields = $textareaFields ?? [];
@endphp

@if(count($__locales) > 0)
    <div class="rounded-xl border border-violet-200 bg-violet-50/40 p-3">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-violet-700">
            🌐 Translations
        </p>

        @foreach($__locales as $__loc)
            <div class="mt-3 first:mt-0 rounded-lg border border-violet-200 bg-admin-card p-3">
                <div class="mb-2 flex items-center gap-2">
                    <span class="rounded bg-violet-600 px-1.5 py-0.5 text-[10px] font-bold uppercase text-white">
                        {{ $__loc }}
                    </span>
                    <span class="text-xs text-admin-secondary opacity-70">
                        Leave blank to fall back to the default language.
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach($fields as $__field => $__flabel)
                        <div>
                            <label class="admin-form-label">{{ $__flabel }}</label>

                            @if(in_array($__field, $__textareaFields, true))
                                <textarea name="translations[{{ $__loc }}][{{ $__field }}]" rows="2" class="admin-input">{{ $record->rawTranslation($__field, $__loc) }}</textarea>
                            @else
                                <input type="text"
                                       name="translations[{{ $__loc }}][{{ $__field }}]"
                                       value="{{ $record->rawTranslation($__field, $__loc) }}"
                                       class="admin-input">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

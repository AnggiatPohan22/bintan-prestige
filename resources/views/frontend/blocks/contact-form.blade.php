@php
    $formDefId = $data['form_definition_id'] ?? null;
    $formDef   = $formDefId ? \App\Models\FormDefinition::find($formDefId) : null;
    $successKey = 'contact_success_' . ($formDef?->id ?? 0);
@endphp

@if($formDef)
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">

            @if(!empty($data['title']))
                <h2 class="mb-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $data['title'] }}</h2>
            @endif
            @if(!empty($data['description']))
                <p class="mb-8 text-slate-500">{{ $data['description'] }}</p>
            @endif

            @if(session($successKey))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800">
                    <i class="fa-solid fa-circle-check mr-2"></i>
                    {{ session($successKey) }}
                </div>
            @else
                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('forms.submit', $formDef->slug) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    {{-- Honeypot --}}
                    <div style="display:none;position:absolute;left:-9999px;" aria-hidden="true">
                        <input type="text" name="_hp" tabindex="-1" autocomplete="off" value="">
                    </div>

                    @foreach($formDef->fields as $field)
                        @php
                            $fieldName = $field['name'];
                            $fieldLabel = $field['label'];
                            $required = $field['required'] ?? false;
                            $type = $field['type'];
                        @endphp

                        <div>
                            <label for="cf_{{ $formDef->id }}_{{ $fieldName }}" class="block mb-1.5 text-sm font-medium text-slate-700">
                                {{ $fieldLabel }}
                                @if($required) <span class="text-red-500">*</span> @endif
                            </label>

                            @if($type === 'textarea')
                                <textarea
                                    id="cf_{{ $formDef->id }}_{{ $fieldName }}"
                                    name="{{ $fieldName }}"
                                    rows="4"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 @error($fieldName) border-red-400 @enderror"
                                    {{ $required ? 'required' : '' }}
                                >{{ old($fieldName) }}</textarea>

                            @elseif($type === 'select')
                                <select
                                    id="cf_{{ $formDef->id }}_{{ $fieldName }}"
                                    name="{{ $fieldName }}"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500"
                                    {{ $required ? 'required' : '' }}
                                >
                                    <option value="">— Select —</option>
                                    @foreach($field['options'] ?? [] as $option)
                                        <option value="{{ $option }}" @selected(old($fieldName) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>

                            @elseif($type === 'checkbox')
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        id="cf_{{ $formDef->id }}_{{ $fieldName }}"
                                        name="{{ $fieldName }}"
                                        value="1"
                                        class="rounded border-slate-300"
                                        {{ old($fieldName) ? 'checked' : '' }}
                                        {{ $required ? 'required' : '' }}
                                    >
                                    {{ $fieldLabel }}
                                </label>

                            @elseif($type === 'file')
                                <input
                                    type="file"
                                    id="cf_{{ $formDef->id }}_{{ $fieldName }}"
                                    name="{{ $fieldName }}"
                                    class="w-full text-sm text-slate-500"
                                    {{ $required ? 'required' : '' }}
                                >

                            @else
                                <input
                                    type="{{ $type === 'phone' ? 'tel' : $type }}"
                                    id="cf_{{ $formDef->id }}_{{ $fieldName }}"
                                    name="{{ $fieldName }}"
                                    value="{{ old($fieldName) }}"
                                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 @error($fieldName) border-red-400 @enderror"
                                    {{ $required ? 'required' : '' }}
                                >
                            @endif

                            @error($fieldName)
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach

                    <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                        {{ $formDef->submitLabel() }}
                    </button>
                </form>
            @endif
        </div>
    </section>
@endif

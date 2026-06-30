{{--
    Rich text — stores HTML string.
    Alpine x-data exposes content for the hidden input; TipTap/Quill wired at B4.
--}}
<div
    x-data="{
        content: @js(old($inputName, $value ?? '')),
        init() {
            this.$el.querySelector('[data-richtext-display]').innerHTML = this.content || '';
        }
    }"
    class="rounded-lg border {{ $hasError ? 'border-red-400' : 'border-slate-200' }}"
>
    {{-- Toolbar placeholder — replaced by real editor in B4 --}}
    <div class="flex items-center gap-1 border-b border-slate-200 bg-slate-50 px-3 py-2">
        <span class="text-xs font-medium text-admin-secondary">Rich Text</span>
        <span class="ml-auto text-[10px] text-slate-400">Full editor loads in entry form</span>
    </div>

    <div
        data-richtext-display
        contenteditable="true"
        class="min-h-[120px] p-3 text-sm text-admin-secondary focus:outline-none"
        @input="content = $event.target.innerHTML"
    ></div>
</div>

<input type="hidden" name="{{ $inputName }}" :value="content">

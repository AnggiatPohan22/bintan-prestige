@props([
    'submitLabel'  => 'Save',
    'cancelRoute'  => null,
    'cancelLabel'  => 'Cancel',
    'status'       => null,
    'statusField'  => 'is_active',
    'statusOptions' => [
        '1' => 'Active',
        '0' => 'Inactive',
    ],
    'statusColors' => [
        '1' => 'success',
        '0' => 'warning',
    ],
])

<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="text-sm font-bold text-slate-700">Publish</h3>
    </div>

    <div class="admin-card-body space-y-4">

        @if($status !== null && count($statusOptions) > 0)
            <div>
                <label class="admin-form-label" for="publish-box-status">Status</label>
                <select
                    id="publish-box-status"
                    name="{{ $statusField }}"
                    class="admin-input"
                >
                    @foreach($statusOptions as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected((string) $status === (string) $value)
                        >{{ $label }}</option>
                    @endforeach
                </select>

                @foreach($statusOptions as $value => $label)
                    @if((string) $status === (string) $value)
                        @php $color = $statusColors[$value] ?? 'info'; @endphp
                        <span class="mt-2 inline-flex admin-badge-{{ $color }}">
                            {{ $label }}
                        </span>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Extra slot for additional controls --}}
        @if(isset($extra))
            <div>{{ $extra }}</div>
        @endif

        <div class="space-y-2 border-t border-slate-100 pt-4">
            <button type="submit" class="admin-btn-primary w-full">
                {{ $submitLabel }}
            </button>

            @if($cancelRoute)
                <a href="{{ route($cancelRoute) }}" class="admin-btn-secondary w-full text-center">
                    {{ $cancelLabel }}
                </a>
            @endif
        </div>

    </div>
</div>

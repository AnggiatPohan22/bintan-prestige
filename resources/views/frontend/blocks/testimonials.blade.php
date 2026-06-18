@php
    $data  = $block->data ?? [];
    $items = array_filter($data['items'] ?? [], fn ($t) => ! empty($t['name']) || ! empty($t['text']));
    $bg      = $data['background'] ?? [];
    $bgStyle = '';
    if (! empty($bg['color'])) $bgStyle .= 'background-color:' . e($bg['color']) . ';';
    if (! empty($bg['image'])) {
        $bgImgUrl = str_starts_with($bg['image'], 'http') ? $bg['image'] : asset('storage/' . $bg['image']);
        $bgStyle .= 'background-image:url(' . $bgImgUrl . ');background-size:' . e($bg['size'] ?? 'cover') . ';background-position:' . e($bg['position'] ?? 'center') . ';background-repeat:' . e($bg['repeat'] ?? 'no-repeat') . ';';
    }
@endphp

@if(count($items))
<section class="{{ empty($bg['color']) && empty($bg['image']) ? 'bg-slate-50' : '' }} py-16" style="{{ $bgStyle }}">
    <div class="mx-auto max-w-6xl px-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $testimonial)
                @php
                    $name   = $testimonial['name'] ?? '';
                    $text   = $testimonial['text'] ?? '';
                    $rating = min(5, max(1, (int) ($testimonial['rating'] ?? 5)));
                    $avatar = $testimonial['avatar'] ?? '';
                    $avatarUrl = $avatar
                        ? (str_starts_with($avatar, 'http') ? $avatar : asset('storage/' . $avatar))
                        : null;
                @endphp
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="mb-3 flex text-yellow-400">
                        @for($s = 1; $s <= 5; $s++)
                            <i class="fa-{{ $s <= $rating ? 'solid' : 'regular' }} fa-star text-sm"></i>
                        @endfor
                    </div>

                    @if($text)
                        <p class="text-sm leading-relaxed text-slate-600">"{{ $text }}"</p>
                    @endif

                    @if($name)
                        <div class="mt-4 flex items-center gap-3">
                            @if($avatarUrl)
                                <img
                                    src="{{ $avatarUrl }}"
                                    alt="{{ $name }}"
                                    class="h-9 w-9 rounded-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-600">
                                    {{ strtoupper(substr($name, 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-sm font-semibold text-slate-800">{{ $name }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

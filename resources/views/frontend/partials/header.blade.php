@php
    $navigationSettings = $navigationSettings ?? \App\Support\NavigationSettings::valuesFromSettings(collect());
    $navigationItems = $navigationSettings['items'] ?? [];
    $headerCtaLabel = $navigationSettings['cta_label'] ?? 'Plan Trip';
    $headerCtaUrl = \App\Support\NavigationSettings::resolveUrl($navigationSettings['cta_url'] ?? '/#whatsapp-cta');
    $tracksHeaderWhatsapp = str_contains($headerCtaUrl, 'wa.me')
        || str_contains($headerCtaUrl, 'whatsapp')
        || str_contains($headerCtaUrl, '#whatsapp-cta');
    $isStickyHeader = (bool) ($navigationSettings['is_sticky'] ?? true);
    $headerStyleVariables = collect([
        '--header-nav-color' => $navigationSettings['menu_text_color'] ?? null,
        '--header-nav-hover-color' => $navigationSettings['menu_hover_color'] ?? null,
        '--header-nav-scrolled-color' => $navigationSettings['scrolled_menu_text_color'] ?? null,
        '--header-nav-scrolled-hover-color' => $navigationSettings['scrolled_menu_hover_color'] ?? null,
        '--header-dropdown-text-color' => $navigationSettings['dropdown_text_color'] ?? null,
        '--header-dropdown-hover-background' => $navigationSettings['dropdown_hover_background'] ?? null,
    ])
        ->filter()
        ->map(fn ($value, $key) => $key . ': ' . $value)
        ->implode('; ');
@endphp

<header
    class="frontend-header {{ $isStickyHeader ? '' : 'frontend-header--inline' }}"
    @if($isStickyHeader) data-frontend-header @endif
    @if($headerStyleVariables) style="{{ $headerStyleVariables }}" @endif
>
    <div class="frontend-header__inner">
        <a href="{{ route('home') }}"
           class="frontend-brand"
           aria-label="{{ ($businessIdentity['brand_name'] ?? 'Bintan Prestige') }} home">
            @php
                $headerLogo = isset($siteAssets) ? (($siteAssets['site.logo.dark'] ?? null) ?: ($siteAssets['site.logo'] ?? null)) : null;
                $brandName = $businessIdentity['brand_name'] ?? 'Bintan Prestige';
                $brandInitials = collect(explode(' ', $brandName))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
            @endphp
            <span class="frontend-brand__mark">
                @if($headerLogo?->url)
                    <img src="{{ $headerLogo->url }}" alt="{{ $headerLogo->alt ?: $brandName . ' logo' }}">
                @else
                    {{ $brandInitials ?: 'BP' }}
                @endif
            </span>
            <span>{{ $brandName }}</span>
        </a>

        <nav class="frontend-nav" aria-label="Main navigation">
            @foreach($navigationItems as $item)
                @php
                    $isExternal = (bool) ($item['is_external'] ?? false);
                    $itemUrl = \App\Support\NavigationSettings::resolveUrl($item['url'] ?? '#');
                    $children = $item['children'] ?? [];
                    $hasChildren = ! empty($children);
                    $isActive = \App\Support\NavigationSettings::isActiveUrl($item['url'] ?? '#')
                        || collect($children)->contains(fn ($child) => \App\Support\NavigationSettings::isActiveUrl($child['url'] ?? '#'));
                @endphp

                @if($hasChildren)
                    <div class="frontend-nav__item">
                        <a href="{{ $itemUrl }}"
                           class="frontend-nav__link frontend-nav__link--dropdown {{ $isActive ? 'frontend-nav__link--active' : '' }}"
                           @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>
                            {{ $item['label'] }}
                            <svg class="frontend-nav__chevron" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M4 6l4 4 4-4"></path>
                            </svg>
                        </a>

                        <div class="frontend-nav__dropdown" aria-label="{{ $item['label'] }} submenu">
                            @foreach($children as $child)
                                @php
                                    $childUrl = \App\Support\NavigationSettings::resolveUrl($child['url'] ?? '#');
                                    $childExternal = (bool) ($child['is_external'] ?? false);
                                    $childActive = \App\Support\NavigationSettings::isActiveUrl($child['url'] ?? '#');
                                @endphp

                                <a href="{{ $childUrl }}"
                                   class="frontend-nav__dropdown-link {{ $childActive ? 'frontend-nav__dropdown-link--active' : '' }}"
                                   @if($childExternal) target="_blank" rel="noopener noreferrer" @endif>
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ $itemUrl }}"
                       class="frontend-nav__link {{ $isActive ? 'frontend-nav__link--active' : '' }}"
                       @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="frontend-header__actions">
            <a
                href="{{ $headerCtaUrl }}"
                class="btn btn-outline btn-sm frontend-header__cta"
                @if($tracksHeaderWhatsapp)
                    data-whatsapp-tracking="header"
                    data-tracking-label="{{ $headerCtaLabel }}"
                @endif
            >
                {{ $headerCtaLabel }}
            </a>

            <a
                href="{{ $headerCtaUrl }}"
                class="btn btn-outline btn-icon frontend-header__icon"
                aria-label="{{ $headerCtaLabel }}"
                @if($tracksHeaderWhatsapp)
                    data-whatsapp-tracking="header"
                    data-tracking-label="{{ $headerCtaLabel }}"
                @endif
            >
                <svg
                    class="frontend-header__icon-svg"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M7 17L17 7"></path>
                    <path d="M9 7h8v8"></path>
                </svg>
            </a>
        </div>
    </div>
</header>

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Product listing pagination">
        <ul class="product-pagination__list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="product-pagination__link product-pagination__link--disabled" aria-disabled="true">
                        Previous page
                    </span>
                @else
                    <a
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                        class="product-pagination__link"
                        aria-label="Go to previous product listing page"
                    >
                        Previous
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="product-pagination__link product-pagination__link--disabled" aria-hidden="true">
                            {{ $element }}
                        </span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="product-pagination__link product-pagination__link--current" aria-current="page">
                                    <span class="sr-only">Current page </span>{{ $page }}
                                </span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    class="product-pagination__link"
                                    aria-label="Go to product listing page {{ $page }}"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                        class="product-pagination__link"
                        aria-label="Go to next product listing page"
                    >
                        Next
                    </a>
                @else
                    <span class="product-pagination__link product-pagination__link--disabled" aria-disabled="true">
                        Next page
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif

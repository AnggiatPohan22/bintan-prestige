# SEO & AI Discovery Documentation

Reserved for SEO defaults, structured data, sitemap, robots, Open Graph, AI discovery, and crawlability documentation.

Existing SEO-related global settings docs remain in their original locations and are indexed from `docs/README.md`.

## Public Product Listing Rendering Policy

The public Product Listing uses the existing frontend layout and shared metadata partials. `ProductController@index` prepares listing-specific SEO state instead of creating a second metadata system.

Metadata policy:

- Base `/products`: listing title and description from CMS-backed listing content or code fallback; canonical to `/products`; robots `index, follow`.
- Plain pagination with valid results: self-canonical per page; robots `index, follow`.
- Filter, sort, price-range, invalid filter, unsupported query, and high-page URLs: canonical to `/products`; robots `noindex, follow`.
- Query values used in metadata come from normalized database-backed state, not raw request input.

Structured data policy:

- BreadcrumbList is rendered through the existing structured-data builder when global structured data is enabled.
- Product Listing ItemList is rendered only from the current public listed Product paginator.
- ItemList contains Product names, positions, and crawlable Product Detail URLs.
- Listing ItemList does not include ratings, reviews, availability, or Product offers; Product-level schema remains a Product Detail concern.

AI discovery policy:

- Product names, category, destination, duration, price state, Product Detail links, pagination links, and empty-state copy remain visible in server-rendered HTML.
- Product Listing content is not rendered through client-only JavaScript and does not use hidden keyword blocks.
- Public Product links remain crawlable anchors and are not marked `nofollow`.

## Public Product Detail Rendering Policy

The public Product Detail page uses the existing frontend layout metadata partials. `ProductController::show()` prepares Product-specific metadata through `ProductDetailDisplayState`; Blade receives title, description, canonical, robots, Open Graph type/image, and JSON-LD context instead of creating a second metadata system.

Metadata policy:

- Published public Product Detail pages use Product metadata when present, then prepared Product content fallback, with self-canonical URLs and `index, follow` robots.
- Open Graph and Twitter metadata are rendered by the shared social-share partial; Product Detail sets `og:type` to `product` through prepared metadata state.
- Product Detail content remains visible in server-rendered HTML and is not moved into crawler-only hidden text.

Structured data policy:

- Product schema uses actual Product name, plain-text description, canonical URL, Product media when available, category, brand, and actual positive prices only.
- IDR and SGD prices create separate Offer entries; missing or zero prices create no Offer.
- BreadcrumbList follows the visible breadcrumb state.
- FAQPage is emitted only when visible FAQ items have both a real question and a real answer.
- Product Detail schema does not include fake availability, ratings, reviews, SKU, GTIN, inventory, booking status, or currency conversion.

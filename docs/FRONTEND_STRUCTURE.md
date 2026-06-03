# Frontend Structure

Frontend Blade files live in `resources/views/frontend`.

## Homepage Architecture

Homepage-only sections live in:

```text
resources/views/frontend/home/
```

Current homepage sections:

- `hero.blade.php`
- `popular-tour.blade.php`
- `popular-products.blade.php`
- `categories.blade.php`
- `about-journey.blade.php`
- `explore-banner.blade.php`
- `testimonials.blade.php`
- `faq.blade.php`

`home.blade.php` should include sections and avoid long section markup.

## Reusable Partials

Global reusable blocks live in:

```text
resources/views/frontend/partials/
```

Examples:

- `header.blade.php`
- `footer.blade.php`
- `manual-ads.blade.php`

## Components

Small repeated UI blocks live in:

```text
resources/views/frontend/components/
```

Product cards should remain componentized and continue using product data from the `products` table.

## Data Source Rules

- Products come from the `products` table.
- Categories come from the `categories` table.
- FAQs come from the `faqs` table.
- PageSection is only for static marketing content and fallback copy.

## Hero Media Slider

`frontend/home/hero.blade.php` can render up to 10 uploaded PageSection media records as a lightweight slider. Animation is controlled by the PageSection admin editor.

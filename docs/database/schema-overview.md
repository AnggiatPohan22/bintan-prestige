# Database Schema Overview

Last updated: 2026-06-13

This page is the current canonical database summary after the DB-01 through DB-09 improvement phase.

Historical reports remain in `ai/reports/database/` and `ai/reports/performance/`. Older migrations were not edited; database improvements were applied through new migrations.

## Core CMS Tables

Current core CMS data tables:

- `products`
- `categories`
- `destinations`
- `product_prices`
- `product_images`
- `product_features`
- `product_faqs`
- `product_itineraries`
- `product_notes`
- `site_settings`
- `site_assets`

## Products

`products` stores public/admin product records and publication state.

Category/Destination parent integrity:

- `products.category_id` references `categories.id`.
- `products.destination_id` references `destinations.id`.
- Parent category/destination hard deletes are restricted while products reference them.
- Category and Destination archive flow uses soft delete, so archiving does not delete Product rows.
- Product parent columns remain required and are not nullable.

Current product performance index:

- `products_status_created_at_index` on `products(status, created_at)`.

Purpose:

- Supports published/draft product queries that filter by `status`.
- Supports newest/latest product listing sorted by `created_at`.
- Supports dashboard/admin status counts partially through the leading `status` column.

Notes:

- The index is non-unique.
- No product data or product query behavior is changed by this index.
- Product price range optimization remains deferred; `product_prices(currency, price)` is not added yet.

Status policy:

- Product publication status uses string values.
- Supported current values are `draft` and `published`.
- `ProductFactory` now defaults to `published` and has explicit `draft()` and `published()` states.

## Product Prices

`product_prices` stores product prices by currency.

Current rule:

- One product can have one `IDR` price.
- One product can have one `SGD` price.
- The same product cannot have duplicate rows for the same currency.
- Price amounts must be numeric and non-negative through the product Form Request and ProductPrice service flow.

Integrity:

- `product_prices.product_id` references `products.id`.
- Product prices cascade when the parent product is deleted.
- `product_prices(product_id, currency)` is unique through `product_prices_product_id_currency_unique`.

Supported currencies:

- `IDR`
- `SGD`

Deferred:

- `product_prices(currency, price)` remains deferred until focused price filter/sort tests justify the additional index.

## Product Child Tables

Product-owned child tables remain attached to `products.id`:

- `product_images`
- `product_features`
- `product_faqs`
- `product_itineraries`
- `product_notes`

Product-owned child rows still cascade when the Product itself is deleted. DB-09 did not change product child foreign keys.

## Global Settings Tables

`site_settings` stores backend-managed global display settings.

`site_assets` stores backend-managed global display assets.

Public reads are centralized through `App\Services\GlobalSettingsService`.

Cache keys:

- `global_settings.public.v1`
- `global_assets.public.v1`

Cache TTL:

- 30 minutes

Public cache stores primitive payloads and hydrates runtime collections/models after cache reads. Future private credentials, API tokens, or secrets must not be added to the public global settings cache.

# Backend Structure

## Controllers

Admin controllers live in `app/Http/Controllers/Admin`.

Frontend controllers live in `app/Http/Controllers/Frontend`.

## Services

Reusable business logic lives in `app/Services`.

`PageSectionService` centralizes active PageSection lookups and admin listing queries.

## Requests

Request validation lives in `app/Http/Requests`.

Admin-specific requests live in `app/Http/Requests/Admin`.

## Models

Models live in `app/Models`.

`PageSection` represents static CMS section content.

`PageSectionMedia` represents reusable uploaded images that belong to a PageSection. Media uploads are stored per page and section folder.

`Faq` represents dynamic FAQ records for pages.

## FAQ Module

FAQ admin CRUD is available through `admin.faqs.*` routes. The homepage only loads active FAQ records ordered by `sort_order`.

## PageSection vs Dynamic Modules

Use PageSection for section-level copy, image paths, buttons, and status.

Use dedicated tables for repeated dynamic records:

- Products: `products`
- Categories: `categories`
- FAQs: `faqs`

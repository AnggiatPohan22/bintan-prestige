# Database Relationships

## Products and Prices

`Product` has many `ProductPrice` rows.

`ProductPrice` belongs to `Product`.

Integrity rules:

- Product price rows are owned by a Product.
- Deleting a Product cascades its price rows.
- A Product may have both `IDR` and `SGD` prices.
- A Product may not have duplicate price rows for the same currency.

Canonical write path:

- Admin product create/update flows use `ProductPriceService::sync()`.
- The service writes prices with `updateOrCreate()` by `product_id` and `currency`.

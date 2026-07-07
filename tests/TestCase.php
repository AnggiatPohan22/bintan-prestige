<?php

namespace Tests;

use App\Models\Product;
use App\Support\ProductDetailDisplayState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safety net: refuse to run against anything but the sqlite test database.
     *
     * RefreshDatabase runs migrate:fresh (DROP ALL TABLES) in setUp. If config is
     * cached to the real MySQL connection, phpunit.xml's sqlite override is ignored
     * and the dev database gets wiped. This guard aborts BEFORE any migration runs.
     */
    protected function setUp(): void
    {
        // Boot the app first so config is resolved (this does NOT run migrations),
        // then verify the DB connection BEFORE parent::setUp() triggers
        // RefreshDatabase's migrate:fresh.
        $this->refreshApplication();

        $default  = (string) config('database.default');
        $driver   = (string) config("database.connections.{$default}.driver");
        $database = (string) config("database.connections.{$default}.database");

        if ($driver !== 'sqlite' || ($database !== ':memory:' && ! str_ends_with($database, '.sqlite'))) {
            throw new \RuntimeException(
                "SAFETY ABORT: tests must use the sqlite (:memory:) database, but the default "
                ."connection resolved to driver='{$driver}' database='{$database}'. Config is "
                ."probably cached — run `php artisan config:clear` before testing."
            );
        }

        parent::setUp();
    }

    protected function productDetailDisplayState(Product $product, array $globalViewData = []): array
    {
        $product->loadMissing([
            'category',
            'destination',
            'prices',
            'images' => fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
            'highlights',
            'features',
            'faqs',
            'itineraries',
            'notes',
        ]);

        return ProductDetailDisplayState::make($product, [
            'siteAssets' => collect(),
            'defaultMediaSettings' => [],
            'businessIdentity' => [],
            'contactInformation' => [],
            'bookingCtaSettings' => [],
            ...$globalViewData,
        ]);
    }
}

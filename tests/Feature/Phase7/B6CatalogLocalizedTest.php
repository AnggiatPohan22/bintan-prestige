<?php

namespace Tests\Feature\Phase7;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B6 — Catalog localized. Products / Categories / Destinations translate their
 * copy columns per locale via the B1 sidecar (extend-only). Base columns keep
 * default-locale text; prices/images/relations/slugs stay shared.
 */
class B6CatalogLocalizedTest extends TestCase
{
    use RefreshDatabase;

    private function category(): Category
    {
        return Category::create([
            'name' => 'Island Hopping', 'slug' => 'island-hopping',
            'description' => 'Best island tours.', 'is_active' => true,
        ]);
    }

    private function destination(): Destination
    {
        return Destination::create([
            'name' => 'Lagoi', 'slug' => 'lagoi',
            'description' => 'Bintan resort area.', 'is_active' => true,
        ]);
    }

    private function product(): Product
    {
        $c = $this->category();
        $d = $this->destination();

        return Product::create([
            'category_id' => $c->id, 'destination_id' => $d->id,
            'name' => 'Sunrise Boat Tour', 'slug' => 'sunrise-boat-tour',
            'short_description' => 'Early-morning boat tour.',
            'description' => 'Full description.',
            'cta_title' => 'Book now',
            'cta_button_text' => 'Chat via WhatsApp',
            'meta_title' => 'Sunrise Boat Tour | Bintan',
            'status' => 'published',
        ]);
    }

    // ------------------------------------------------------------- category

    public function test_category_accessor_localizes_name_and_description(): void
    {
        $c = $this->category();
        $c->setTranslation('name', 'id', 'Menyusuri Pulau');
        $c->setTranslation('description', 'id', 'Tur pulau terbaik.');
        $c = $c->fresh();

        app()->setLocale('en');
        $this->assertSame('Island Hopping', $c->name);
        $this->assertSame('Best island tours.', $c->description);

        app()->setLocale('id');
        $this->assertSame('Menyusuri Pulau', $c->name);
        $this->assertSame('Tur pulau terbaik.', $c->description);
    }

    public function test_category_falls_back_to_base_when_untranslated(): void
    {
        $c = $this->category();
        app()->setLocale('id');
        $this->assertSame('Island Hopping', $c->fresh()->name);
    }

    // ------------------------------------------------------------- destination

    public function test_destination_accessor_localizes(): void
    {
        $d = $this->destination();
        $d->setTranslation('name', 'id', 'Lagoi (ID)');

        app()->setLocale('id');
        $this->assertSame('Lagoi (ID)', $d->fresh()->name);
    }

    // ------------------------------------------------------------- product

    public function test_product_accessor_localizes_multiple_fields(): void
    {
        $p = $this->product();
        $p->setTranslation('name', 'id', 'Tur Perahu Sunrise');
        $p->setTranslation('short_description', 'id', 'Tur perahu pagi.');
        $p->setTranslation('cta_button_text', 'id', 'Chat via WhatsApp');
        $p->setTranslation('meta_title', 'id', 'Tur Perahu Sunrise | Bintan');

        app()->setLocale('id');
        $p = $p->fresh();
        $this->assertSame('Tur Perahu Sunrise', $p->name);
        $this->assertSame('Tur perahu pagi.', $p->short_description);
        $this->assertSame('Chat via WhatsApp', $p->cta_button_text);
        $this->assertSame('Tur Perahu Sunrise | Bintan', $p->meta_title);
        // Untranslated field falls back to base.
        $this->assertSame('Full description.', $p->description);
    }

    public function test_default_locale_short_circuits_to_base_no_queries(): void
    {
        $p = $this->product();
        $p->setTranslation('name', 'id', 'Nama Baru');

        app()->setLocale('en');
        $fresh = Product::query()->withTranslations()->find($p->id);

        DB::flushQueryLog();
        DB::enableQueryLog();
        // Default locale reads base column directly — no extra query.
        $name = $fresh->name;
        $this->assertCount(0, DB::getQueryLog());
        $this->assertSame('Sunrise Boat Tour', $name);
        DB::disableQueryLog();
    }

    public function test_with_translations_scope_avoids_n_plus_one(): void
    {
        $c = $this->category();
        $d = $this->destination();
        foreach (range(1, 3) as $i) {
            $p = Product::create([
                'category_id' => $c->id, 'destination_id' => $d->id,
                'name' => "Tour $i", 'slug' => "tour-$i",
                'status' => 'published',
            ]);
            $p->setTranslation('name', 'id', "Tur $i");
        }

        app()->setLocale('id');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $products = Product::query()->withTranslations()->get();
        $names = $products->map(fn (Product $p) => $p->name)->all();
        // 1 for products + 1 for translations = 2, not 4+.
        $this->assertCount(2, DB::getQueryLog());
        $this->assertContains('Tur 1', $names);
        DB::disableQueryLog();
    }

    // ------------------------------------------------------------- shared attributes

    public function test_shared_attributes_are_not_touched(): void
    {
        $p = $this->product();
        $p->setTranslation('name', 'id', 'Nama ID');
        $p = $p->fresh();

        // Slug, category/destination relations, and status stay identical.
        $this->assertSame('sunrise-boat-tour', $p->slug);
        $this->assertSame('published', $p->status);
        $this->assertNotNull($p->category_id);
        $this->assertNotNull($p->destination_id);
        // Base column preserved.
        $this->assertSame('Sunrise Boat Tour', $p->getRawOriginal('name'));
    }
}

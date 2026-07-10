<?php

namespace Tests\Feature\Phase7;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductFeature;
use App\Models\ProductHighlight;
use App\Models\ProductItinerary;
use App\Models\ProductNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B6.1 — Product child sections localized. Extends the Phase 7 catalog i18n
 * (B6) to product highlights / features / FAQs / itineraries / notes plus the
 * parent's `duration` field. Uses the B1 sidecar (no schema change).
 */
class B6_1ProductChildrenLocalizedTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $c = Category::create(['name' => 'C', 'slug' => 'c', 'is_active' => true]);
        $d = Destination::create(['name' => 'D', 'slug' => 'd', 'is_active' => true]);

        return Product::create([
            'category_id' => $c->id, 'destination_id' => $d->id,
            'name' => 'Sunrise Tour', 'slug' => 'sunrise-tour',
            'duration' => '4 hours', 'status' => 'published',
        ]);
    }

    // ------------------------------------------------------------- parent Product.duration

    public function test_product_duration_localizes(): void
    {
        $p = $this->product();
        $p->setTranslation('duration', 'id', '4 jam');
        $p = $p->fresh();

        app()->setLocale('en');
        $this->assertSame('4 hours', $p->duration);

        app()->setLocale('id');
        $this->assertSame('4 jam', $p->duration);
    }

    // ------------------------------------------------------------- children accessors

    public function test_highlight_title_localizes(): void
    {
        $p = $this->product();
        $h = $p->highlights()->create(['title' => 'Hotel Pickup', 'icon' => 'fa-car', 'sort_order' => 0]);
        $h->setTranslation('title', 'id', 'Jemput Hotel');

        app()->setLocale('id');
        $this->assertSame('Jemput Hotel', $h->fresh()->title);
    }

    public function test_feature_value_localizes_but_label_is_shared(): void
    {
        $p = $this->product();
        $f = $p->features()->create(['label' => 'included', 'value' => 'Lunch included', 'sort_order' => 0]);
        $f->setTranslation('value', 'id', 'Makan siang termasuk');

        app()->setLocale('id');
        $fresh = $f->fresh();
        $this->assertSame('Makan siang termasuk', $fresh->value);
        // `label` is a system key — must stay identical across locales for filtering/grouping.
        $this->assertSame('included', $fresh->label);
    }

    public function test_faq_question_and_answer_localize(): void
    {
        $p = $this->product();
        $q = $p->faqs()->create([
            'question' => 'Is lunch included?',
            'answer' => 'Yes, lunch is included in the package.',
            'sort_order' => 0,
        ]);
        $q->setTranslation('question', 'id', 'Apakah makan siang termasuk?');
        $q->setTranslation('answer', 'id', 'Ya, makan siang sudah termasuk paket.');

        app()->setLocale('id');
        $fresh = $q->fresh();
        $this->assertSame('Apakah makan siang termasuk?', $fresh->question);
        $this->assertSame('Ya, makan siang sudah termasuk paket.', $fresh->answer);
    }

    public function test_itinerary_time_title_description_localize_but_start_time_is_shared(): void
    {
        $p = $this->product();
        $it = $p->itineraries()->create([
            'time' => 'Morning', 'title' => 'Pickup',
            'description' => 'Pickup from hotel',
            'start_time' => 800, 'sort_order' => 0,
        ]);
        $it->setTranslation('time', 'id', 'Pagi');
        $it->setTranslation('title', 'id', 'Penjemputan');
        $it->setTranslation('description', 'id', 'Penjemputan dari hotel');

        app()->setLocale('id');
        $fresh = $it->fresh();
        $this->assertSame('Pagi', $fresh->time);
        $this->assertSame('Penjemputan', $fresh->title);
        $this->assertSame('Penjemputan dari hotel', $fresh->description);
        // start_time is a real clock value — stays shared.
        $this->assertSame(800, $fresh->start_time);
    }

    public function test_note_title_description_localize(): void
    {
        $p = $this->product();
        $n = $p->notes()->create(['title' => 'What to bring', 'description' => 'Sunscreen', 'sort_order' => 0]);
        $n->setTranslation('title', 'id', 'Yang perlu dibawa');
        $n->setTranslation('description', 'id', 'Tabir surya');

        app()->setLocale('id');
        $fresh = $n->fresh();
        $this->assertSame('Yang perlu dibawa', $fresh->title);
        $this->assertSame('Tabir surya', $fresh->description);
    }

    // ------------------------------------------------------------- N+1 fence (C2-style)

    public function test_child_translation_queries_stay_flat_regardless_of_count(): void
    {
        $p = $this->product();

        // Baseline: 3 highlights
        for ($i = 1; $i <= 3; $i++) {
            $h = $p->highlights()->create(['title' => "H $i", 'sort_order' => $i]);
            $h->setTranslation('title', 'id', "H $i ID");
        }

        app()->setLocale('id');
        DB::flushQueryLog(); DB::enableQueryLog();
        $count = ProductHighlight::query()
            ->where('product_id', $p->id)
            ->withTranslations()
            ->get()
            ->map(fn (ProductHighlight $h) => $h->title)
            ->count();
        $baselineQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(3, $count);

        // Scale to 15 highlights
        for ($i = 4; $i <= 15; $i++) {
            $h = $p->highlights()->create(['title' => "H $i", 'sort_order' => $i]);
            $h->setTranslation('title', 'id', "H $i ID");
        }

        DB::flushQueryLog(); DB::enableQueryLog();
        $count = ProductHighlight::query()
            ->where('product_id', $p->id)
            ->withTranslations()
            ->get()
            ->map(fn (ProductHighlight $h) => $h->title)
            ->count();
        $scaledQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(15, $count);
        $this->assertSame($baselineQueries, $scaledQueries,
            "Child translation queries scaled with row count ({$baselineQueries} → {$scaledQueries}). N+1 regression.");
    }

    // ------------------------------------------------------------- admin write via controller

    public function test_admin_highlight_update_persists_translation(): void
    {
        $p = $this->product();
        $h = $p->highlights()->create(['title' => 'Hotel Pickup', 'sort_order' => 0]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.products.highlights.update', $h), [
                'title' => 'Hotel Pickup',
                'icon' => 'fa-car',
                'sort_order' => 0,
                'translations' => ['id' => ['title' => 'Jemput Hotel']],
            ])
            ->assertRedirect();

        $this->assertSame('Jemput Hotel', $h->fresh()->rawTranslation('title', 'id'));
    }

    public function test_admin_faq_update_persists_both_translations(): void
    {
        $p = $this->product();
        $q = $p->faqs()->create([
            'question' => 'Is lunch included?',
            'answer' => 'Yes.',
            'sort_order' => 0,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.products.faqs.update', $q), [
                'question' => 'Is lunch included?',
                'answer' => 'Yes.',
                'sort_order' => 0,
                'translations' => [
                    'id' => [
                        'question' => 'Apakah makan siang termasuk?',
                        'answer' => 'Ya, sudah termasuk.',
                    ],
                ],
            ])
            ->assertRedirect();

        $fresh = $q->fresh();
        $this->assertSame('Apakah makan siang termasuk?', $fresh->rawTranslation('question', 'id'));
        $this->assertSame('Ya, sudah termasuk.', $fresh->rawTranslation('answer', 'id'));
    }

    public function test_empty_translation_clears_the_sidecar(): void
    {
        $p = $this->product();
        $h = $p->highlights()->create(['title' => 'Hotel Pickup', 'sort_order' => 0]);
        $h->setTranslation('title', 'id', 'Jemput Hotel');

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.products.highlights.update', $h), [
                'title' => 'Hotel Pickup',
                'icon' => null,
                'sort_order' => 0,
                'translations' => ['id' => ['title' => '']], // clear
            ])
            ->assertRedirect();

        $this->assertNull($h->fresh()->rawTranslation('title', 'id'));
    }
}

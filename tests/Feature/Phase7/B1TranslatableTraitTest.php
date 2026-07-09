<?php

namespace Tests\Feature\Phase7;

use App\Models\Concerns\Translatable;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * B1 — `translations` sidecar + Translatable trait, tested in isolation against
 * a throwaway table so no protected model is involved yet. Verifies resolution,
 * fallback, default-locale semantics, the eager-load N+1 guard, and delete cleanup.
 */
class B1TranslatableTraitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('translatable_dummies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        app()->setLocale('en'); // default locale for a clean baseline
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('translatable_dummies');
        parent::tearDown();
    }

    // ------------------------------------------------------------- resolution

    public function test_default_locale_reads_base_column(): void
    {
        $m = TranslatableDummy::create(['name' => 'English Name']);
        $m->setTranslation('name', 'id', 'Nama Indonesia');
        $m->refresh();

        $this->assertSame('English Name', $m->translate('name', 'en'));
    }

    public function test_non_default_locale_reads_translation(): void
    {
        $m = TranslatableDummy::create(['name' => 'English Name']);
        $m->setTranslation('name', 'id', 'Nama Indonesia');
        $m->refresh();

        $this->assertSame('Nama Indonesia', $m->translate('name', 'id'));
    }

    public function test_missing_translation_falls_back_to_base(): void
    {
        $m = TranslatableDummy::create(['name' => 'English Name']);
        $m->refresh();

        $this->assertSame('English Name', $m->translate('name', 'id'));
    }

    public function test_empty_translation_falls_back_to_base(): void
    {
        $m = TranslatableDummy::create(['name' => 'English Name']);
        $m->setTranslation('name', 'id', 'Nama');
        $m->setTranslation('name', 'id', '');   // clear → row removed
        $m->refresh();

        $this->assertSame('English Name', $m->translate('name', 'id'));
        $this->assertSame(0, $m->translations()->count());
    }

    public function test_non_translatable_field_ignores_sidecar(): void
    {
        $m = TranslatableDummy::create(['name' => 'A', 'description' => 'Desc EN']);
        // Force a rogue sidecar row for an undeclared field.
        Translation::create([
            'translatable_type' => $m->getMorphClass(),
            'translatable_id'   => $m->id,
            'locale' => 'id', 'field' => 'description', 'value' => 'Desc ID',
        ]);
        $m->refresh();

        // 'description' is NOT in $translatable → always base column.
        $this->assertSame('Desc EN', $m->translate('description', 'id'));
    }

    public function test_current_locale_is_used_when_none_passed(): void
    {
        $m = TranslatableDummy::create(['name' => 'English Name']);
        $m->setTranslation('name', 'id', 'Nama Indonesia');
        $m->refresh();

        app()->setLocale('id');
        $this->assertSame('Nama Indonesia', $m->translate('name'));

        app()->setLocale('en');
        $this->assertSame('English Name', $m->translate('name'));
    }

    // ------------------------------------------------------------- writes

    public function test_set_translation_default_locale_writes_base_column(): void
    {
        $m = TranslatableDummy::create(['name' => 'Old']);
        $m->setTranslation('name', 'en', 'New Base');
        $m->save();
        $m->refresh();

        $this->assertSame('New Base', $m->name);
        $this->assertSame(0, $m->translations()->count()); // nothing in sidecar
    }

    public function test_set_translation_upserts_single_row(): void
    {
        $m = TranslatableDummy::create(['name' => 'Base']);
        $m->setTranslation('name', 'id', 'Satu');
        $m->setTranslation('name', 'id', 'Dua'); // update, not insert
        $m->refresh();

        $this->assertSame('Dua', $m->translate('name', 'id'));
        $this->assertSame(1, $m->translations()->where('field', 'name')->count());
    }

    // ------------------------------------------------------------- N+1 guard

    public function test_with_translations_scope_avoids_n_plus_one(): void
    {
        foreach (range(1, 3) as $i) {
            $m = TranslatableDummy::create(['name' => "Base {$i}"]);
            $m->setTranslation('name', 'id', "Nama {$i}");
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $models = TranslatableDummy::query()->withTranslations('id')->get();
        $resolved = $models->map(fn (TranslatableDummy $m) => $m->translate('name', 'id'))->all();

        // 1 query for the models + 1 for their translations. No per-attribute query.
        $this->assertCount(2, DB::getQueryLog());
        $this->assertSame(['Nama 1', 'Nama 2', 'Nama 3'], $resolved);

        DB::disableQueryLog();
    }

    public function test_with_translations_scope_loads_only_target_locale(): void
    {
        $m = TranslatableDummy::create(['name' => 'Base']);
        $m->setTranslation('name', 'id', 'Nama');

        $loaded = TranslatableDummy::query()->withTranslations('id')->find($m->id);
        $this->assertTrue($loaded->relationLoaded('translations'));
        $this->assertCount(1, $loaded->translations);
        $this->assertSame('id', $loaded->translations->first()->locale);
    }

    // ------------------------------------------------------------- delete cleanup

    public function test_hard_delete_purges_translations(): void
    {
        $m = TranslatableDummy::create(['name' => 'Base']);
        $m->setTranslation('name', 'id', 'Nama');

        $m->delete();

        $this->assertSame(0, Translation::where('translatable_id', $m->id)->count());
    }

    public function test_soft_delete_keeps_translations_force_delete_purges(): void
    {
        $m = SoftTranslatableDummy::create(['name' => 'Base']);
        $m->setTranslation('name', 'id', 'Nama');

        $m->delete(); // soft
        $this->assertSame(1, Translation::where('translatable_type', $m->getMorphClass())->count());

        $m->forceDelete();
        $this->assertSame(0, Translation::where('translatable_type', $m->getMorphClass())->count());
    }
}

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 */
class TranslatableDummy extends Model
{
    use Translatable;

    protected $table = 'translatable_dummies';

    protected $guarded = [];

    /** @var list<string> */
    protected array $translatable = ['name'];
}

class SoftTranslatableDummy extends Model
{
    use SoftDeletes;
    use Translatable;

    protected $table = 'translatable_dummies';

    protected $guarded = [];

    /** @var list<string> */
    protected array $translatable = ['name'];
}

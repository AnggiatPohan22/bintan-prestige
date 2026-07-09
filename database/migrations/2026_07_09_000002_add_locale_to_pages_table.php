<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Phase 7 (B4) — Pages become row-per-locale.
 *
 * Each locale is a full page record linked by `translation_group_id`; the default
 * locale keeps its existing (unprefixed) slug and SEO. Additive + reversible.
 * A mysqldump backup is taken before this runs (dev DB carries recovery data).
 *
 * Existing rows backfill: locale = 'en' (default), and each row gets its own
 * fresh ULID group (every current page is its own group until translated).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('slug');
            $table->char('translation_group_id', 26)->nullable()->after('locale');
            $table->index('translation_group_id');
        });

        // Backfill a unique group id per existing row (each page = its own group).
        DB::table('pages')->select('id')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('pages')->where('id', $row->id)->update([
                    'locale' => 'en',
                    'translation_group_id' => (string) Str::ulid(),
                ]);
            }
        });

        // Slug is unique per locale now, not globally.
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('pages_slug_unique');
            $table->unique(['slug', 'locale'], 'pages_slug_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('pages_slug_locale_unique');
            $table->unique('slug', 'pages_slug_unique');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['translation_group_id']);
            $table->dropColumn(['locale', 'translation_group_id']);
        });
    }
};

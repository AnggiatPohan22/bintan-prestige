<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Phase 7 (B5) — Content Entries become row-per-locale.
 *
 * Each locale is a full entry linked by translation_group_id; default locale
 * keeps its unprefixed URL/SEO. Additive + reversible. Every existing entry
 * backfills locale='en' and gets its own fresh ULID group. A mysqldump backup
 * is taken before this runs (dev DB carries recovery data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_entries', function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('slug');
            $table->char('translation_group_id', 26)->nullable()->after('locale');
            $table->index('translation_group_id');
        });

        DB::table('content_entries')->select('id')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('content_entries')->where('id', $row->id)->update([
                    'locale' => 'en',
                    'translation_group_id' => (string) Str::ulid(),
                ]);
            }
        });

        // Slug is unique per (content_type, locale) now.
        Schema::table('content_entries', function (Blueprint $table) {
            $table->dropUnique(['content_type_id', 'slug']);
            $table->unique(['content_type_id', 'slug', 'locale'], 'content_entries_type_slug_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::table('content_entries', function (Blueprint $table) {
            $table->dropUnique('content_entries_type_slug_locale_unique');
            $table->unique(['content_type_id', 'slug']);
        });

        Schema::table('content_entries', function (Blueprint $table) {
            $table->dropIndex(['translation_group_id']);
            $table->dropColumn(['locale', 'translation_group_id']);
        });
    }
};

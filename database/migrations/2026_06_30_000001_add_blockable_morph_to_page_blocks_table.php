<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 — A3: make page_blocks polymorphic (dual-rail, Strategy 1).
 *
 * Adds blockable_type/blockable_id so a block can belong to a Page (existing)
 * OR a future ContentEntry. Pages keep using page_id/hasMany — the Phase 5
 * builder is untouched. Entries (wired at B9) will set blockable_* with a NULL
 * page_id, which is why page_id is relaxed to nullable here.
 *
 * Additive + reversible: down() drops only the new columns/index and restores
 * page_id NOT NULL. No existing data is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_blocks', function (Blueprint $table): void {
            $table->string('blockable_type')->nullable()->after('page_id');
            $table->unsignedBigInteger('blockable_id')->nullable()->after('blockable_type');
            $table->index(['blockable_type', 'blockable_id']);
        });

        // Relax page_id to nullable so non-Page owners can live in this shared
        // table. The existing FK + cascadeOnDelete are preserved.
        Schema::table('page_blocks', function (Blueprint $table): void {
            $table->unsignedBigInteger('page_id')->nullable()->change();
        });

        // Backfill: every existing block belongs to a Page. Mirror page_id into
        // blockable_* so the morph is uniform across all rows (dual-rail —
        // page_id stays authoritative for pages).
        DB::table('page_blocks')->update([
            'blockable_type' => Page::class,
            'blockable_id' => DB::raw('page_id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('page_blocks', function (Blueprint $table): void {
            $table->dropIndex(['blockable_type', 'blockable_id']);
            $table->dropColumn(['blockable_type', 'blockable_id']);
        });

        // Restore NOT NULL — safe because page-owned rows always retain page_id.
        Schema::table('page_blocks', function (Blueprint $table): void {
            $table->unsignedBigInteger('page_id')->nullable(false)->change();
        });
    }
};

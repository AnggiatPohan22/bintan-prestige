<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6.1 S2 — media collections (storage structure by position).
     * Nullable + additive: existing rows stay untouched ("Uncategorized").
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('collection', 50)->nullable()->after('disk');
            $table->index('collection');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['collection']);
            $table->dropColumn('collection');
        });
    }
};

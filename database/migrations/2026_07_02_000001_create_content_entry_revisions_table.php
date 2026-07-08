<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entry_revisions', function (Blueprint $table) {
            $table->id();

            // CASCADE: a revision has no meaning without its entry. Isolated to
            // content_entries only — does not touch page_revisions or any other
            // relation, so future changes to entry structure never break page logic.
            $table->foreignId('content_entry_id')
                ->constrained('content_entries')
                ->cascadeOnDelete();

            $table->unsignedInteger('revision_number')->default(1);

            // A single flexible JSON snapshot of the entry's full revisionable
            // state (core columns + data + seo). Storing it as one JSON column
            // means adding entry columns later needs NO revisions-schema change.
            $table->json('snapshot');

            // Keep the revision even if the author is later deleted.
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['content_entry_id', 'revision_number'], 'idx_cer_entry_revision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entry_revisions');
    }
};

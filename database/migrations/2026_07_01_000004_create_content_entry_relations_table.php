<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entry_relations', function (Blueprint $table) {
            $table->id();

            // Both sides CASCADE — a relation row is a disposable projection of a
            // relationship-type field value. Deleting either endpoint removes the
            // link automatically, so no dangling references survive.
            $table->foreignId('source_entry_id')
                ->constrained('content_entries')
                ->cascadeOnDelete();
            $table->foreignId('target_entry_id')
                ->constrained('content_entries')
                ->cascadeOnDelete();

            // Which relationship field on the source produced this link.
            $table->string('field_key', 100);

            // Preserves the order the target IDs appear in the source's data array.
            $table->unsignedInteger('sort_order')->default(0);

            // Forward lookup: "targets of entry X via field Y".
            $table->index(['source_entry_id', 'field_key']);
            // Reverse lookup: "which entries link TO entry Z".
            $table->index('target_entry_id');

            // One row per (source, field, target) — an entry cannot link to the
            // same target twice through the same field.
            $table->unique(
                ['source_entry_id', 'field_key', 'target_entry_id'],
                'cer_source_field_target_unique'
            );

            // No timestamps — pure projection cache (mirrors content_entry_index).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entry_relations');
    }
};

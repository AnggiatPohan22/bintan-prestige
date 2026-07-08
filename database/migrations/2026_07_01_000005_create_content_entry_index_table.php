<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entry_index', function (Blueprint $table) {
            $table->id();

            // CASCADE — index rows are disposable projections; delete them with the entry.
            $table->foreignId('content_entry_id')
                ->constrained('content_entries')
                ->cascadeOnDelete();

            $table->string('field_key', 100);

            // One column per value type; only one is non-null per row.
            $table->string('value_string', 500)->nullable();
            $table->decimal('value_number', 15, 6)->nullable();
            $table->date('value_date')->nullable();

            // One row per entry × field combination.
            $table->unique(['content_entry_id', 'field_key']);

            // Support fast WHERE field_key = ? AND value_* = ? queries.
            $table->index(['field_key', 'value_string']);
            $table->index(['field_key', 'value_number']);
            $table->index(['field_key', 'value_date']);

            // No timestamps — pure projection cache.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entry_index');
    }
};

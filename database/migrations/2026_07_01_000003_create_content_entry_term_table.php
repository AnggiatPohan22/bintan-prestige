<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entry_term', function (Blueprint $table) {
            // Pivot rows are disposable — both sides CASCADE on hard delete
            $table->foreignId('content_entry_id')
                ->constrained('content_entries')
                ->cascadeOnDelete();
            $table->foreignId('term_id')
                ->constrained('terms')
                ->cascadeOnDelete();
            $table->primary(['content_entry_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entry_term');
    }
};

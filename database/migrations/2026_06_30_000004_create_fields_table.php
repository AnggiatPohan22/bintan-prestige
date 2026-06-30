<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('field_group_id')->constrained('field_groups')->cascadeOnDelete();

            // Matches a key in config/field-types.php (FieldTypeRegistry).
            $table->string('type', 50);

            // Snake-case identifier (e.g. "star_rating") — unique within its group.
            $table->string('key', 100);

            $table->string('label', 150);
            $table->text('instructions')->nullable();

            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);

            // Per-type settings (resolved from the catalog's settings_schema at B3).
            $table->json('settings')->nullable();

            // Default value for new entries.
            $table->json('default_value')->nullable();

            // Conditional display logic (evaluated at B3 / B4).
            $table->json('conditional_logic')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['field_group_id', 'key']);
            $table->index(['field_group_id', 'sort_order']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};

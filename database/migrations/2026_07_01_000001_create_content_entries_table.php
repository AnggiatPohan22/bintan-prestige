<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table): void {
            $table->id();

            // RESTRICT (not CASCADE): archiving a content type must never silently
            // wipe all its entries. Force-delete is blocked at the app layer too.
            $table->foreignId('content_type_id')
                ->constrained('content_types')
                ->restrictOnDelete();

            // Core supported features — each nullable so non-supporting types are unaffected.
            $table->string('title', 500)->nullable();

            // Unique within a content type, not globally.
            // MySQL unique index allows multiple NULLs, so types that don't support
            // slugs can have many entries with slug = null without constraint errors.
            $table->string('slug', 200)->nullable();

            $table->text('excerpt')->nullable();

            // varchar, not MySQL ENUM: adding a new status later requires only a
            // code change, not an ALTER TABLE that can lock a large table.
            $table->string('status', 20)->default('draft');

            $table->timestamp('published_at')->nullable();

            // SET NULL: deleting a user must not cascade-delete their content.
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Optional per-entry template override (e.g. "full-width", "sidebar").
            $table->string('template', 100)->nullable();

            // Manual ordering within a content type.
            $table->unsignedInteger('sort_order')->default(0);

            // All custom field values keyed by field->key.
            // New fields added by admin never require a migration — old entries simply
            // lack that key and fall back to the field's default_value.
            $table->json('data')->nullable();

            // SEO meta stored separately from custom field data so both can evolve
            // independently. Keys: title, description, og_image, canonical.
            $table->json('seo')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Compound unique: slug namespace is per content type.
            $table->unique(['content_type_id', 'slug']);

            // Most common queries: all published entries of a type, ordered by date.
            $table->index(['content_type_id', 'status', 'published_at']);

            // Listing by author.
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entries');
    }
};

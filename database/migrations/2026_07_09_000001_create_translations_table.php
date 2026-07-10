<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 (B1) — polymorphic attribute-translation sidecar.
 *
 * One row per (model, locale, field). The DEFAULT-locale value always stays in
 * the model's own base column, so protected modules keep working even if Phase 7
 * were reverted — this table only holds the NON-default translations. Extend-only:
 * no protected table is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner (Product, Category, Destination, PageSection,
            // MenuItem, Term, SiteSetting, …). string(255) type + unsignedBigInteger id.
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');

            $table->string('locale', 10);
            $table->string('field', 100);
            $table->longText('value')->nullable();

            $table->timestamps();

            // One value per (owner, locale, field). Named to stay under MySQL's
            // 64-char identifier limit.
            $table->unique(
                ['translatable_type', 'translatable_id', 'locale', 'field'],
                'translations_owner_locale_field_unique'
            );

            // Eager-load lookup: "all translations for this owner in locale X".
            $table->index(
                ['translatable_type', 'translatable_id', 'locale'],
                'translations_owner_locale_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};

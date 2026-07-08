<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_types', function (Blueprint $table): void {
            $table->id();

            // Identity
            $table->string('slug', 100)->unique();
            $table->string('label_singular', 150);
            $table->string('label_plural', 150);
            $table->string('icon', 50)->default('file-lines');
            $table->text('description')->nullable();

            // Public routing
            $table->boolean('is_public')->default(true);
            $table->boolean('has_archive')->default(true);
            $table->string('route_base', 100)->unique()->nullable();

            // Supported features (JSON array of: title|slug|editor|excerpt|featured_image|revisions|scheduling|seo)
            $table->json('supports')->nullable();

            // Admin ordering
            $table->unsignedInteger('menu_position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'menu_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_types');
    }
};

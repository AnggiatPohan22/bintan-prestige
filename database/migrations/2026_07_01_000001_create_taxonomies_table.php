<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label_singular');
            $table->string('label_plural');
            $table->text('description')->nullable();
            $table->boolean('is_hierarchical')->default(false);
            // NULL = applies to all content types; array of IDs = restricted
            $table->json('content_type_ids')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('area', 100);
            $table->string('widget_type', 50);
            $table->string('title', 255)->nullable();
            $table->json('data')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['theme_id', 'area']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_type_id')->constrained('content_types')->cascadeOnDelete();
            $table->string('label', 150);
            $table->string('key', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['content_type_id', 'key']);
            $table->index(['content_type_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_groups');
    }
};

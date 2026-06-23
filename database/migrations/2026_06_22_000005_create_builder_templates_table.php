<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->string('template_type', 50)->default('page')->index();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->foreignId('base_template_id')->nullable()->constrained('page_templates')->nullOnDelete();
            $table->string('thumbnail', 2048)->nullable();
            $table->json('template_data');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_templates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->index();
            $table->string('section_key')->index();
            $table->string('label')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('mobile_image')->nullable();
            $table->json('extra_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['page_key', 'section_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};

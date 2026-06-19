<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('directory', 255);
            $table->string('version', 30)->nullable();
            $table->string('author', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('screenshot', 255)->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};

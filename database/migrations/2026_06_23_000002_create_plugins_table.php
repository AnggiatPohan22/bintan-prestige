<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version')->default('1.0.0');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('config')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index('is_active', 'idx_plugins_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};

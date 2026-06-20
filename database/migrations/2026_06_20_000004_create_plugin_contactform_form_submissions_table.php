<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('form_definitions')->cascadeOnDelete();
            $table->json('data');
            $table->boolean('is_read')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['form_id', 'is_read'], 'idx_sub_form_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};

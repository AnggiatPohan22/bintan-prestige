<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_hash', 64);
            $table->string('referrer', 500)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->date('viewed_date');
            $table->timestamps();

            $table->index(['page_id', 'viewed_date'], 'idx_pv_page_date');
            $table->index('viewed_date', 'idx_pv_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};

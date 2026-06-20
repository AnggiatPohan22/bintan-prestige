<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_view_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->timestamps();

            $table->unique(['page_id', 'stat_date'], 'uk_page_date');
            $table->index('stat_date', 'idx_pvds_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_view_daily_stats');
    }
};

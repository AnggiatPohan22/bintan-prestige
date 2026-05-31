<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'product_itineraries',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('product_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('time')
                    ->nullable();

                $table->string('title');

                $table->text('description')
                    ->nullable();

                // IMPORTANT
                $table->integer('start_time')
                    ->default(0);

                $table->integer('sort_order')
                    ->default(0);

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_itineraries'
        );
    }
};
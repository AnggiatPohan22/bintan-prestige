<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('destination_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('slug')
                ->unique();

            $table->string('thumbnail')
                ->nullable();

            $table->text('short_description')
                ->nullable();

            $table->longText('description')
                ->nullable();

            $table->string('meeting_point')
                ->nullable();

            $table->string('duration')
                ->nullable();

            $table->decimal('price_from', 12, 2)
                ->nullable();

            $table->string('whatsapp_number')
                ->nullable();

            $table->boolean('is_featured')
                ->default(false);

            $table->enum(
                    'status',
                    [
                        'draft',
                        'published'
                    ]
                )->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
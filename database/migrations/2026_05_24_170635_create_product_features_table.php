<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'product_features',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('product_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->enum(
                    'label',
                    [
                        'included',
                        'excluded',
                        'optional',
                        'addon',
                        'important',
                    ]
                );

                // content text
                // example:
                // Hotel Pickup
                // Ferry Ticket
                // Insurance
                $table->string('value');

                $table->integer('sort_order')
                    ->default(0);

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_features'
        );
    }
};
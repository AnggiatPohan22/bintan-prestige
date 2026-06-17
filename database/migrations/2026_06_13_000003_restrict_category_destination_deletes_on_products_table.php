<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['destination_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id', 'products_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();

            $table->foreign('destination_id', 'products_destination_id_foreign')
                ->references('id')
                ->on('destinations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['destination_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id', 'products_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();

            $table->foreign('destination_id', 'products_destination_id_foreign')
                ->references('id')
                ->on('destinations')
                ->cascadeOnDelete();
        });
    }
};

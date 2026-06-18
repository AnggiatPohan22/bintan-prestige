<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // template_id column already exists (nullable). Add the FK now that
            // page_templates exists. Deleting a template nulls the reference.
            $table->foreign('template_id')
                ->references('id')
                ->on('page_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });
    }
};

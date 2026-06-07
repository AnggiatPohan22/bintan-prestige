<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_section_media', function (Blueprint $table) {
            $table->string('object_fit')->nullable()->after('alt');
            $table->string('object_position')->nullable()->after('object_fit');
        });
    }

    public function down(): void
    {
        Schema::table('page_section_media', function (Blueprint $table) {
            $table->dropColumn(['object_fit', 'object_position']);
        });
    }
};

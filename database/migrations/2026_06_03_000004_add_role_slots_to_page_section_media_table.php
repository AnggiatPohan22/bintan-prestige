<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_section_media', function (Blueprint $table) {
            $table->string('role')->default('gallery')->after('page_section_id')->index();
            $table->string('slot_key')->default('gallery')->after('role')->index();
            $table->string('label')->nullable()->after('slot_key');
        });
    }

    public function down(): void
    {
        Schema::table('page_section_media', function (Blueprint $table) {
            $table->dropColumn(['role', 'slot_key', 'label']);
        });
    }
};

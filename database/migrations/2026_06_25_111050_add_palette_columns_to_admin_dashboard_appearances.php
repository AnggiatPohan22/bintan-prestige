<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_dashboard_appearances', function (Blueprint $table) {
            // 10-section token bag per mode. Each is ~45 hex keys
            // (surfaces+text+buttons+forms+badges+tables+alerts+modal+topbar+sidebar).
            $table->json('dark_palette')->nullable()->after('mode');
            $table->json('light_palette')->nullable()->after('dark_palette');

            // Active starter preset name per mode (e.g. 'Command Center Dark', 'Full Light').
            $table->string('dark_preset_name', 80)->nullable()->after('light_palette');
            $table->string('light_preset_name', 80)->nullable()->after('dark_preset_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_dashboard_appearances', function (Blueprint $table) {
            $table->dropColumn(['dark_palette', 'light_palette', 'dark_preset_name', 'light_preset_name']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_dashboard_appearances', function (Blueprint $table) {
            $table->id();

            // Display mode — maps to data-admin-mode HTML attribute
            $table->enum('mode', ['dark', 'light', 'light_classic'])->default('dark');

            // Sidebar
            $table->string('sidebar_bg', 20)->default('#020617');
            $table->enum('sidebar_style', ['dark', 'light'])->default('dark');

            // Primary color (buttons, active state, focus ring)
            $table->string('primary_color', 20)->default('#7C3AED');
            $table->string('primary_hover', 20)->default('#6D28D9');
            $table->string('primary_text', 20)->default('#FFFFFF');

            // Accent color
            $table->string('accent_color', 20)->default('#06B6D4');

            // Brand gold
            $table->string('gold_color', 20)->default('#D4AF37');
            $table->boolean('show_gold')->default(true);

            // Background overrides (page, card, input)
            $table->string('bg_base', 20)->default('#020617');
            $table->string('bg_card', 20)->default('#1E293B');
            $table->string('bg_input', 20)->default('#0F172A');

            // Future extensions — store arbitrary CSS vars without new migrations
            // e.g. {"--admin-radius-lg": "16px", "--admin-font-size-base": "14px"}
            $table->json('custom_vars')->nullable();

            // Preset tracking
            $table->string('preset_name', 100)->nullable();
            $table->boolean('is_default')->default(false);

            // Audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_dashboard_appearances');
    }
};

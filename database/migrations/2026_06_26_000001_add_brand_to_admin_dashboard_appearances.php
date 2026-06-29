<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_dashboard_appearances', function (Blueprint $table) {
            $table->string('brand_abbr', 10)->default('BP')->after('light_preset_name');
            $table->string('brand_name', 100)->default('Travel Admin')->after('brand_abbr');
            $table->string('brand_tagline', 200)->default('Bintan Prestige')->after('brand_name');
        });
    }

    public function down(): void
    {
        Schema::table('admin_dashboard_appearances', function (Blueprint $table) {
            $table->dropColumn(['brand_abbr', 'brand_name', 'brand_tagline']);
        });
    }
};

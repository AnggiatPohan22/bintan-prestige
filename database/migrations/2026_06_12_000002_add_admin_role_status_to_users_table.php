<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('admin')->after('is_admin')->index();
            $table->boolean('is_active')->default(true)->after('role')->index();
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });

        $adminIds = DB::table('users')
            ->where('is_admin', true)
            ->orderBy('id')
            ->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $adminIds)
            ->update([
                'role' => 'admin',
                'is_active' => true,
            ]);

        DB::table('users')
            ->where('id', $adminIds->first())
            ->update([
                'role' => 'super_admin',
                'is_active' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['role', 'is_active', 'created_by']);
        });
    }
};

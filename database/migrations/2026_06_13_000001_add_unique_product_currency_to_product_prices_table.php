<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'product_prices_product_id_currency_unique';

    public function up(): void
    {
        $duplicateExists = DB::table('product_prices')
            ->select('product_id', 'currency')
            ->groupBy('product_id', 'currency')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateExists) {
            throw new RuntimeException(
                'Cannot add unique product price index while duplicate product_id + currency rows exist.'
            );
        }

        Schema::table('product_prices', function (Blueprint $table) {
            $table->unique(
                ['product_id', 'currency'],
                self::INDEX_NAME
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropUnique(self::INDEX_NAME);
        });
    }
};

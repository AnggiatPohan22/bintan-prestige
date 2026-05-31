<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'products',
            function (Blueprint $table) {

                // pickup info
                $table->boolean('pickup_available')
                    ->default(false)
                    ->after('meeting_point');

                $table->string('pickup_type')
                    ->nullable()
                    ->after('pickup_available');

                $table->text('pickup_note')
                    ->nullable()
                    ->after('pickup_type');

                // CTA
                $table->string('cta_title')
                    ->nullable();

                $table->text('cta_description')
                    ->nullable();

                $table->string('cta_button_text')
                    ->nullable();

                // SEO
                $table->string('meta_title')
                    ->nullable();

                $table->text('meta_description')
                    ->nullable();

                $table->text('meta_keywords')
                    ->nullable();

                $table->string('canonical_url')
                    ->nullable();

                $table->string('og_image')
                    ->nullable();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'products',
            function (Blueprint $table) {

                $table->dropColumn([
                    'pickup_available',
                    'pickup_type',
                    'pickup_note',

                    'cta_title',
                    'cta_description',
                    'cta_button_text',

                    'meta_title',
                    'meta_description',
                    'meta_keywords',
                    'canonical_url',
                    'og_image'
                ]);
            }
        );
    }
};
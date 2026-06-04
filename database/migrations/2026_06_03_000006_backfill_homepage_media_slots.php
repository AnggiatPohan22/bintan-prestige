<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillSiteLogo();

        $this->backfillSectionSlot('home.hero', 'image', 'background', 'desktop_background', 'Hero background desktop');
        $this->backfillSectionSlot('home.hero', 'mobile_image', 'background', 'mobile_background', 'Hero background mobile');
        $this->backfillSectionSlot('home.manual_ads', 'image', 'frame', 'main_visual', 'Gambar promo utama');
        $this->backfillSectionSlot('home.explore_banner', 'image', 'background', 'desktop_background', 'Background banner desktop');
        $this->backfillSectionSlot('home.explore_banner', 'mobile_image', 'background', 'mobile_background', 'Background banner mobile');

        $this->backfillPopularTourGallerySlots();
    }

    public function down(): void
    {
        DB::table('page_section_media')
            ->whereIn('label', [
                'Hero background desktop',
                'Hero background mobile',
                'Gambar promo utama',
                'Background banner desktop',
                'Background banner mobile',
            ])
            ->whereIn('role', ['background', 'frame'])
            ->delete();
    }

    private function backfillSiteLogo(): void
    {
        if (DB::table('site_assets')->where('key', 'site.logo')->exists()) {
            return;
        }

        $section = DB::table('page_sections')
            ->where('section_key', 'home.popular_tour')
            ->whereNotNull('image')
            ->first();

        if (! $section) {
            return;
        }

        DB::table('site_assets')->insert([
            'key' => 'site.logo',
            'label' => 'Main website logo',
            'path' => $section->image,
            'alt' => 'Main website logo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function backfillSectionSlot(string $sectionKey, string $sourceColumn, string $role, string $slotKey, string $label): void
    {
        $section = DB::table('page_sections')
            ->where('section_key', $sectionKey)
            ->whereNotNull($sourceColumn)
            ->first();

        if (! $section || ! $section->{$sourceColumn}) {
            return;
        }

        $exists = DB::table('page_section_media')
            ->where('page_section_id', $section->id)
            ->where('role', $role)
            ->where('slot_key', $slotKey)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('page_section_media')->insert([
            'page_section_id' => $section->id,
            'role' => $role,
            'slot_key' => $slotKey,
            'label' => $label,
            'path' => $section->{$sourceColumn},
            'alt' => $label,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function backfillPopularTourGallerySlots(): void
    {
        $section = DB::table('page_sections')
            ->where('section_key', 'home.popular_tour')
            ->first();

        if (! $section) {
            return;
        }

        $slots = [
            ['left_wide', 'Frame kiri atas'],
            ['left_small', 'Frame kiri bawah'],
            ['right_wide', 'Frame kanan atas'],
            ['right_small', 'Frame kanan bawah'],
        ];

        $hasFrameSlots = DB::table('page_section_media')
            ->where('page_section_id', $section->id)
            ->where('role', 'frame')
            ->whereIn('slot_key', array_column($slots, 0))
            ->exists();

        if ($hasFrameSlots) {
            return;
        }

        $galleryItems = DB::table('page_section_media')
            ->where('page_section_id', $section->id)
            ->where('role', 'gallery')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(4)
            ->get();

        foreach ($galleryItems as $index => $media) {
            [$slotKey, $label] = $slots[$index];

            DB::table('page_section_media')
                ->where('id', $media->id)
                ->update([
                    'role' => 'frame',
                    'slot_key' => $slotKey,
                    'label' => $label,
                    'alt' => $media->alt ?: $label,
                    'sort_order' => 0,
                    'updated_at' => now(),
                ]);
        }
    }
};

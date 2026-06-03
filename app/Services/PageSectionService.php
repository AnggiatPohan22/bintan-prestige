<?php

namespace App\Services;

use App\Models\PageSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PageSectionService
{
    public function getPageSections(string $pageKey): Collection
    {
        if (! Schema::hasTable('page_sections')) {
            return collect();
        }

        return PageSection::with('activeMedia')
            ->where('page_key', $pageKey)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('section_key');
    }

    public function getHomeSections(): Collection
    {
        return $this->getPageSections('home');
    }

    public function getAdminSections(): Collection
    {
        if (! Schema::hasTable('page_sections')) {
            return collect();
        }

        return PageSection::query()
            ->withCount('media')
            ->orderBy('page_key')
            ->orderBy('sort_order')
            ->orderBy('section_key')
            ->get();
    }

    public function tableExists(): bool
    {
        return Schema::hasTable('page_sections');
    }
}

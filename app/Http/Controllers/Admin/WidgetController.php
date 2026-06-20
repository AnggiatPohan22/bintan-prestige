<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWidgetRequest;
use App\Http\Requests\Admin\UpdateWidgetRequest;
use App\Models\Theme;
use App\Models\Widget;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WidgetController extends Controller
{
    public function index(Theme $theme): View
    {
        $areas   = $theme->widgetAreas();
        $widgets = $theme->widgets()
            ->ordered()
            ->get()
            ->groupBy('area');

        return view('backend.themes.widgets.index', compact('theme', 'areas', 'widgets'));
    }

    public function create(Theme $theme): View
    {
        $areas      = $theme->widgetAreas();
        $preselArea = request()->query('area', array_key_first($areas) ?? '');

        return view('backend.themes.widgets.create', compact('theme', 'areas', 'preselArea'));
    }

    public function store(StoreWidgetRequest $request, Theme $theme): RedirectResponse
    {
        $theme->widgets()->create([
            'area'        => $request->area,
            'widget_type' => $request->widget_type,
            'title'       => $request->title ?: ucfirst($request->widget_type) . ' Widget',
            'data'        => $this->extractData($request->widget_type, $request->input('data', [])),
            'sort_order'  => $request->input('sort_order', $theme->widgets()->where('area', $request->area)->max('sort_order') + 1),
            'is_visible'  => true,
        ]);

        return redirect()
            ->route('admin.themes.widgets.index', $theme)
            ->with('success', 'Widget added.');
    }

    public function edit(Theme $theme, Widget $widget): View
    {
        $this->ensureWidgetBelongsToTheme($theme, $widget);

        $areas = $theme->widgetAreas();

        return view('backend.themes.widgets.edit', compact('theme', 'widget', 'areas'));
    }

    public function update(UpdateWidgetRequest $request, Theme $theme, Widget $widget): RedirectResponse
    {
        $this->ensureWidgetBelongsToTheme($theme, $widget);

        $widget->update([
            'title'      => $request->input('title', $widget->title),
            'sort_order' => $request->input('sort_order', $widget->sort_order),
            'data'       => $this->extractData($widget->widget_type, $request->input('data', [])),
        ]);

        return redirect()
            ->route('admin.themes.widgets.index', $theme)
            ->with('success', 'Widget updated.');
    }

    public function destroy(Theme $theme, Widget $widget): RedirectResponse
    {
        $this->ensureWidgetBelongsToTheme($theme, $widget);

        $widget->delete();

        return redirect()
            ->route('admin.themes.widgets.index', $theme)
            ->with('success', 'Widget deleted.');
    }

    public function toggleVisible(Theme $theme, Widget $widget): RedirectResponse
    {
        $this->ensureWidgetBelongsToTheme($theme, $widget);

        $widget->update(['is_visible' => ! $widget->is_visible]);

        return redirect()
            ->route('admin.themes.widgets.index', $theme)
            ->with('success', $widget->is_visible ? 'Widget is now visible.' : 'Widget is now hidden.');
    }

    /**
     * Extract and sanitize type-specific data from the submitted form input.
     * Only fields relevant to the widget type are kept.
     */
    private function extractData(string $type, array $raw): array
    {
        return match ($type) {
            'text' => [
                'heading' => $this->clean($raw['heading'] ?? ''),
                'content' => $raw['content'] ?? '',
            ],
            'html' => [
                'code' => $raw['code'] ?? '',
            ],
            'image' => [
                'src'      => $this->clean($raw['src'] ?? ''),
                'alt'      => $this->clean($raw['alt'] ?? ''),
                'link_url' => $this->clean($raw['link_url'] ?? ''),
                'caption'  => $this->clean($raw['caption'] ?? ''),
            ],
            'navigation' => [
                'heading' => $this->clean($raw['heading'] ?? ''),
                'links'   => collect($raw['links'] ?? [])->map(fn ($link) => [
                    'label' => $this->clean($link['label'] ?? ''),
                    'url'   => $this->clean($link['url'] ?? ''),
                ])->filter(fn ($link) => filled($link['label']) || filled($link['url']))->values()->all(),
            ],
            default => [],
        };
    }

    private function clean(string $value): string
    {
        return strip_tags(trim($value));
    }

    private function ensureWidgetBelongsToTheme(Theme $theme, Widget $widget): void
    {
        abort_unless($widget->theme_id === $theme->id, 404);
    }
}

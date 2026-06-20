<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\Plugin\PluginManager;
use App\Services\Plugin\PluginRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class PluginController extends Controller
{
    public function __construct(
        private readonly PluginRegistry $registry,
        private readonly PluginManager $manager,
    ) {}

    public function index(): View
    {
        $plugins = $this->registry->all();

        return view('backend.plugins.index', compact('plugins'));
    }

    public function show(Plugin $plugin): View
    {
        $manifestPath = app_path("Plugins/{$plugin->slug}/plugin.json");
        $manifest     = null;

        if (file_exists($manifestPath)) {
            $raw = file_get_contents($manifestPath);
            if ($raw !== false) {
                $manifest = json_decode($raw, true);
            }
        }

        return view('backend.plugins.show', compact('plugin', 'manifest'));
    }

    public function scan(): RedirectResponse
    {
        $result = $this->registry->sync();

        $parts = [];

        if (count($result['installed']) > 0) {
            $parts[] = count($result['installed']) . ' installed';
        }

        if (count($result['updated']) > 0) {
            $parts[] = count($result['updated']) . ' updated';
        }

        if (count($result['orphaned']) > 0) {
            $parts[] = count($result['orphaned']) . ' orphaned (on disk: no — in DB: yes)';
        }

        $message = $parts
            ? 'Scan complete: ' . implode(', ', $parts) . '.'
            : 'Scan complete. No changes detected.';

        $type = count($result['installed']) > 0 || count($result['updated']) > 0 ? 'success' : 'info';

        return redirect()->route('admin.plugins.index')->with($type, $message);
    }

    public function activate(Plugin $plugin): RedirectResponse
    {
        try {
            $this->manager->activate($plugin);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.plugins.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.plugins.index')
            ->with('success', "\"{$plugin->name}\" has been activated.");
    }

    public function deactivate(Plugin $plugin): RedirectResponse
    {
        try {
            $this->manager->deactivate($plugin);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.plugins.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.plugins.index')
            ->with('success', "\"{$plugin->name}\" has been deactivated.");
    }

    public function destroy(Plugin $plugin): RedirectResponse
    {
        if ($plugin->is_active) {
            return redirect()->route('admin.plugins.index')
                ->with('error', "Deactivate \"{$plugin->name}\" before uninstalling.");
        }

        try {
            $name = $plugin->name;
            $this->manager->uninstall($plugin);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.plugins.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.plugins.index')
            ->with('success', "\"{$name}\" has been uninstalled.");
    }
}

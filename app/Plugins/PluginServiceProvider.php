<?php

namespace App\Plugins;

use App\Contracts\PluginLifecycle;
use Illuminate\Support\ServiceProvider;

/**
 * Base class for all CMS plugin service providers.
 *
 * Each plugin's ServiceProvider should extend this class and override
 * register(), boot(), and the PluginLifecycle methods it needs.
 */
abstract class PluginServiceProvider extends ServiceProvider implements PluginLifecycle
{
    public function onInstall(): void {}

    public function onActivate(): void {}

    public function onDeactivate(): void {}

    public function onUninstall(): void {}
}

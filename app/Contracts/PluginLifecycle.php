<?php

namespace App\Contracts;

interface PluginLifecycle
{
    /** Called once when the plugin is first installed via PluginRegistry::sync(). */
    public function onInstall(): void;

    /** Called each time the plugin is activated from the admin. */
    public function onActivate(): void;

    /** Called each time the plugin is deactivated from the admin. */
    public function onDeactivate(): void;

    /** Called when the plugin is permanently removed. Must clean up all plugin data. */
    public function onUninstall(): void;
}

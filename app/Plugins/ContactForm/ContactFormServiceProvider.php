<?php

namespace App\Plugins\ContactForm;

use App\Plugins\PluginServiceProvider;

class ContactFormServiceProvider extends PluginServiceProvider
{
    public function register(): void {}

    public function boot(): void {}

    public function onUninstall(): void
    {
        // Remove all form definitions and submissions (cascade handles submissions).
        \App\Models\FormDefinition::query()->delete();
    }
}

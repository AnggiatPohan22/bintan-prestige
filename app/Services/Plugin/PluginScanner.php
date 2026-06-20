<?php

namespace App\Services\Plugin;

use App\Exceptions\PluginSecurityException;

class PluginScanner
{
    private const BLOCKLIST = [
        'shell_exec(',  // must come before exec( — exec( is a substring of shell_exec(
        'proc_open(',
        'popen(',
        'passthru(',
        'system(',
        'eval(',
        'exec(',
    ];

    private const ALLOWED_SCOPES = [
        'read:pages',
        'write:pages',
        'read:settings',
        'write:settings',
        'read:users',
        'read:analytics',
        'write:analytics',
        'send:email',
        'manage:redirects',
        'manage:forms',
    ];

    /**
     * Scan all PHP files in a plugin's directory for dangerous functions.
     *
     * @throws PluginSecurityException if a blocked function call is found.
     */
    public function scan(string $slug, ?string $pluginsRoot = null): void
    {
        $pluginDir = ($pluginsRoot ?? app_path('Plugins')) . DIRECTORY_SEPARATOR . $slug;

        if (! is_dir($pluginDir)) {
            return;
        }

        foreach ($this->findPhpFiles($pluginDir) as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            foreach (self::BLOCKLIST as $token) {
                if (str_contains($content, $token)) {
                    throw new PluginSecurityException($slug, $token, $file);
                }
            }
        }
    }

    /**
     * Validate that all declared permission scopes are known to the CMS.
     *
     * @throws PluginSecurityException if an unknown scope is declared.
     */
    public function validatePermissions(array $manifest): void
    {
        $slug        = $manifest['slug'] ?? 'unknown';
        $permissions = $manifest['permissions'] ?? [];

        foreach ($permissions as $scope) {
            if (! in_array($scope, self::ALLOWED_SCOPES, true)) {
                throw new PluginSecurityException($slug, $scope);
            }
        }
    }

    /**
     * @return string[] Absolute paths to all .php files under $dir, recursively.
     *
     * Uses scandir() + explicit path construction so every returned path uses the
     * exact DIRECTORY_SEPARATOR as the input — avoids glob/SplFileInfo path issues on Windows.
     */
    private function findPhpFiles(string $dir): array
    {
        $files = [];

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                array_push($files, ...$this->findPhpFiles($fullPath));
            } elseif (is_file($fullPath) && str_ends_with($item, '.php')) {
                $files[] = $fullPath;
            }
        }

        return $files;
    }
}

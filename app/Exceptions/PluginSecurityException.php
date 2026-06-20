<?php

namespace App\Exceptions;

use RuntimeException;

class PluginSecurityException extends RuntimeException
{
    public function __construct(
        public readonly string $slug,
        public readonly string $blockedToken,
        string $file = '',
    ) {
        $location = $file !== '' ? " in {$file}" : '';

        parent::__construct(
            "Security scan failed for plugin '{$slug}': blocked token '{$blockedToken}' found{$location}"
        );
    }
}

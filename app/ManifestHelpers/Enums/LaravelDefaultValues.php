<?php

namespace App\ManifestHelpers\Enums;

class LaravelDefaultValues extends ManifestDefaultValues
{
    // General Constants
    public const NAMESPACE = 'laravel';

    // Ingress Options
    public const CONTAINER_PORT = 8080;

    // Container Memory Limits
    public const MIN_CONTAINER_LIMIT_MEMORY = 512;   // 512Mi

    public const MIN_CONTAINER_REQUEST_MEMORY = 256;   // 256Mi

    public const MAX_CONTAINER_LIMIT_MEMORY = 16384;  // 16384Mi
}

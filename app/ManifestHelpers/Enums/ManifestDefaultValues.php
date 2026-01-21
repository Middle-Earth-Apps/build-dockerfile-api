<?php

namespace App\ManifestHelpers\Enums;

abstract class ManifestDefaultValues implements ManifestDefaultValuesInterface
{
    // General Constants
    public const NAMESPACE = 'you-need-to-implement-a-namespace';

    // Service Constants
    public const CONTAINER_PORT = 80;

    // Container Memory Limits
    public const MIN_CONTAINER_LIMIT_MEMORY = 512;   // 512Mi

    public const MIN_CONTAINER_REQUEST_MEMORY = 256;   // 256Mi

    public const MAX_CONTAINER_LIMIT_MEMORY = 16384;  // 16384Mi
}

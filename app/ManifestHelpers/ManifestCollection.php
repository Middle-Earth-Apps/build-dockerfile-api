<?php

namespace App\ManifestHelpers;

use App\ManifestHelpers\Enums\ManifestDefaultValues;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ManifestCollection
 * Holds all the manifests for a deployment and provides methods to manipulate them.
 */
class ManifestCollection
{
    public array $manifests = [];

    public string $appName;

    public string $branch;

    public string $buildNumber;

    public ManifestDefaultValues $constants;

    public array $image;

    public array $deployPlan;

    public string $namespace = 'laravel';

    protected string $baseSecretPath = 'dev-team';

    public function __construct(
        string $appName,
        string $branch,
        array $deployPlan,
        array $image,
        string $buildNumber,
        ManifestDefaultValues $constants,
    ) {
        $this->appName = $appName;
        $this->branch = $branch;
        $this->deployPlan = $deployPlan;
        $this->buildNumber = $buildNumber;
        $this->constants = $constants;
        $this->image = $image;
    }

    /*******************************
     *     Core Manifest Methods    *
     *******************************/

    public function addManifest(string $name, array $manifest): void
    {
        $this->manifests[$name] = $manifest;
    }

    public function getManifestArray(string $name): array
    {
        return $this->manifests[$name] ?? [];
    }

    public function getManifestNamesByKind(string $kind): array
    {
        $matchingManifests = [];

        foreach ($this->manifests as $name => $manifest) {
            if (isset($manifest['kind']) && $manifest['kind'] === $kind) {
                $matchingManifests[] = $name;
            }
        }

        return $matchingManifests;
    }

    public function generateAllYaml(): string
    {
        return implode(
            "\n---\n",
            array_map(
                fn ($manifest) => Yaml::dump(
                    $manifest,
                    100,
                    2,
                    Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
                ),
                array_values($this->manifests)
            )
        );
    }

    /*******************************
     *     App/Deployment Info      *
     *******************************/

    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Returns the first 5 characters of the app name.
     */
    public function getAbbreviatedAppName(): string
    {
        return substr($this->appName, 0, 5);
    }

    public function getImageName(): string
    {
        // If image is passed in deploy plan use it, otherwise use default
        return $this->deployPlan['image'] ?? "uadevelopment/{$this->appName}";
    }

    public function getImageNameAndTag(): string
    {
        // If image is passed in deploy plan use it, otherwise use default
        return $this->deployPlan['image'] ?? "uadevelopment/{$this->appName}:{$this->branch}-{$this->buildNumber}";
    }

    public function getNamespace(): string
    {
        return $this->constants::NAMESPACE;
    }

    public function getReplicas(): int
    {
        $replicas = (int) ($this->deployPlan['replicas'] ?? 1);

        return min(max($replicas, 1), 3);
    }

    public function getMaxFileSize(): int
    {
        // return (int) min($this->image['max_file_size'] ?? 20, 200);
        $raw = $this->image['max_file_size'] ?? null;
        $value = is_numeric($raw) ? (int) $raw : 20;

        return min($value, 200);
    }

    public function getSecretPath(): string
    {
        return "{$this->baseSecretPath}/{$this->branch}/{$this->appName}";
    }

    public function getServiceContainerPort(): int
    {
        return $this->deployPlan['service']['targetPort'] ?? $this->constants::CONTAINER_PORT;
    }

    public function getVolumesArray(): array
    {
        return $this->deployPlan['volumes'] ?? [];
    }

    /*******************************
     *        Ingress Methods       *
     *******************************/

    public function getIngressSubDomain(): ?string
    {
        return $this->deployPlan['ingress']['subdomain'] ?? null;
    }

    public function getIngressUrl(): string
    {
        $subdomain = match ($this->branch) {
            'prod' => 'oitapps.ua.edu',
            'test' => 'oitapps-test.ua.edu'
        };

        $path = $this->getIngressSubDomain() ?? $this->appName;

        return "{$path}.{$subdomain}";
    }

    public function getIngressVanityUrl(): ?string
    {
        return $this->branch == 'prod' ? ($this->deployPlan['ingress']['vanity_url'] ?? null) : ($this->branch == 'test' ? ($this->deployPlan['ingress']['vanity_url_test'] ?? null) : null);
    }

    /*******************************
     *       Resource Helpers       *
     *******************************/

    public function getMemoryLimit(?array $resourceValues = null): string
    {
        $resources = $resourceValues ?? ($this->deployPlan['resources'] ?? []);
        $memoryRaw = $resources['memory'] ?? $this->constants::MIN_CONTAINER_LIMIT_MEMORY;

        $memory = $this->parseMemory($memoryRaw);

        // Clamp values
        $memory = min(max($memory, $this->constants::MIN_CONTAINER_LIMIT_MEMORY), $this->constants::MAX_CONTAINER_LIMIT_MEMORY);

        return $this->formatMemory($memory);
    }

    public function getMemoryRequest(?array $resourceValues = null): string
    {
        $resources = $resourceValues ?? ($this->deployPlan['resources'] ?? []);
        $memoryRaw = $resources['memory'] ?? $this->constants::MIN_CONTAINER_LIMIT_MEMORY;

        $memoryLimit = $this->parseMemory($memoryRaw);

        $memoryLimit = min($memoryLimit, $this->constants::MAX_CONTAINER_LIMIT_MEMORY);
        $finalMemory = max((int) ($memoryLimit * 0.5), $this->constants::MIN_CONTAINER_REQUEST_MEMORY);

        return $this->formatMemory($finalMemory);
    }

    /**
     * Parses a Kubernetes memory value string and normalizes it to Mi (mebibytes).
     *
     * Accepts values like "256Mi" or "2Gi" and converts them to an integer representing Mi.
     * Returns null for invalid, negative, or missing values.
     *
     * @param  string|null  $value  The raw memory value from the deploy plan.
     * @return int|null Memory value in Mi, or null if invalid.
     */
    private function parseMemory(?string $value): ?int
    {
        if (is_null($value)) {
            return null;
        }
        if (str_ends_with($value, 'Gi')) {
            $num = rtrim($value, 'Gi');
            if (! is_numeric($num) || $num < 0) {
                return null;
            }

            return (int) $num * 1024;
        }
        if (str_ends_with($value, 'Mi')) {
            $num = rtrim($value, 'Mi');
            if (! is_numeric($num) || $num < 0) {
                return null;
            }

            return (int) $num;
        }
        // Accept only positive numeric values for fallback
        if (is_numeric($value) && $value >= 0) {
            return (int) $value;
        }

        // Invalid format
        return null;
    }

    private function formatMemory(int $value): string
    {
        // Output as "XMi"
        return "{$value}Mi";
    }

    /*******************************
     *        Feature Checks        *
     *******************************/

    public function needsCronJob(): bool
    {
        $cronjobs = $this->deployPlan['cron'] ?? null;

        return is_array($cronjobs) && ! empty($cronjobs);
    }

    public function needsExtraContainers(): bool
    {
        $extraContainers = $this->deployPlan['extra_containers'] ?? null;

        return is_array($extraContainers) && ! empty($extraContainers);
    }

    public function needsIngress(): bool
    {
        //  NULL, missing, or empty array defaults to true
        $ingress = $this->deployPlan['ingress'] ?? true;

        return is_array($ingress) || filter_var($ingress, FILTER_VALIDATE_BOOLEAN);
    }

    public function needsQueue(): bool
    {
        $queueFlag = filter_var($this->deployPlan['queue'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return (is_array($this->deployPlan['queue']) && ! empty($this->deployPlan['queue'])) || $queueFlag;
    }

    public function needsService(): bool
    {
        // If needsIngress is TRUE, then this is TRUE
        // NULL or missing defaults to true
        return $this->needsIngress() || filter_var($this->deployPlan['service'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }
}

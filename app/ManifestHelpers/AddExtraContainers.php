<?php

namespace App\ManifestHelpers;

class AddExtraContainers
{
    public static function run(ManifestCollection $manifestCollection, $extraContainers): void
    {
        $branch = $manifestCollection->branch;
        $deploymentArray = $manifestCollection->getManifestArray('deployment');

        if (is_array($extraContainers)) {
            $index = 1;
            foreach ($extraContainers as $config) {
                if (! empty($config)) {
                    $prodOnly = filter_var($config['prod_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($prodOnly && $branch !== 'prod') {
                        continue;
                    }
                    $name = "extra-container{$index}";
                    $extraContainerArray = self::getExtraContainerYamlArray($manifestCollection, $config, $name);
                    $deploymentArray['spec']['template']['spec']['containers'][] = $extraContainerArray;
                    $index++;
                }
            }
        } else {
            $extraContainerArray = self::getExtraContainerYamlArray($manifestCollection);
            $deploymentArray['spec']['template']['spec']['containers'][] = $extraContainerArray;
        }

        $manifestCollection->addManifest('deployment', $deploymentArray);
        
    }

    private static function getExtraContainerYamlArray(ManifestCollection $manifestCollection, array $extraContainer = [], string $name = 'extra_container'): array
    {
        $memoryLimit = $manifestCollection->getMemoryLimit($extraContainer['resources'] ?? []);
        $memoryRequest = $manifestCollection->getMemoryRequest($extraContainer['resources'] ?? []);

        $containerSpec = [
            'name' => $name,
            'image' => "{$manifestCollection->getImageNameAndTag()}",
            'imagePullPolicy' => 'Always',
            'resources' => [
                'requests' => [
                    'cpu' => '200m',
                    'memory' => $memoryRequest,
                ],
                'limits' => [
                    'memory' => $memoryLimit,
                ],
            ],
        ];

        // Only add command if it exists and isn't empty
        if (!empty($extraContainer['command'])) {
            $containerSpec['command'] = [$extraContainer['command']];

            // Only add args if they exist and are a non-empty array
            if (!empty($extraContainer['args']) && is_array($extraContainer['args'])) {
                $containerSpec['args'] = $extraContainer['args'];
            }
        }

        return $containerSpec;
    }
}

<?php

namespace App\ManifestHelpers;

class AddService
{
    public static function run(ManifestCollection $manifestCollection): void
    {
        $serviceArray = self::getServiceYamlArray($manifestCollection);

        $manifestCollection->addManifest('service', $serviceArray);
    }

    private static function getServiceYamlArray(ManifestCollection $manifestCollection): array
    {
        return [
            'apiVersion' => 'v1',
            'kind' => 'Service',
            'metadata' => [
                'name' => "{$manifestCollection->getAppName()}-svc",
                'namespace' => "{$manifestCollection->getNamespace()}",
                'labels' => [
                    'ua.edu/origin' => 'UA-Deploy-API',
                ],
            ],
            'spec' => [
                'selector' => [
                    'app' => "{$manifestCollection->getAppName()}",
                ],
                'ports' => [
                    [
                        'port' => 80,
                        'targetPort' => $manifestCollection->getServiceContainerPort(),
                    ],
                ],
            ],
        ];
    }
}

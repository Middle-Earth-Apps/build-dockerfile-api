<?php

namespace App\ManifestHelpers;

class AddDeployment
{
    public static function run(ManifestCollection $manifestCollection): void
    {
        $manifestCollection->addManifest(
            name: 'deployment',
            manifest: self::getBaseDeploymentYaml($manifestCollection)
        );
    }

    private static function getBaseDeploymentYaml(ManifestCollection $manifestCollection): array
    {
        $memoryLimit = $manifestCollection->getMemoryLimit();
        $memoryRequest = $manifestCollection->getMemoryRequest();

        return [
            'apiVersion' => 'apps/v1',
            'kind' => 'Deployment',
            'metadata' => [
                'name' => "{$manifestCollection->getAppName()}-deployment",
                'namespace' => "{$manifestCollection->getNamespace()}",
                'labels' => [
                    'app' => "{$manifestCollection->getAppName()}",
                    'ua.edu/origin' => 'UA-Deploy-API',
                ],
            ],
            'spec' => [
                'replicas' => $manifestCollection->getReplicas(),
                'revisionHistoryLimit' => 3,
                'selector' => [
                    'matchLabels' => [
                        'app' => "{$manifestCollection->getAppName()}",
                    ],
                ],
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'app' => "{$manifestCollection->getAppName()}",
                        ],
                    ],
                    'spec' => [
                        'imagePullSecrets' => [
                            [
                                'name' => 'dockerhub',
                            ],
                        ],
                        'containers' => [
                            [
                                'name' => 'primary',
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
                                'ports' => [
                                    [
                                        'containerPort' => 80,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

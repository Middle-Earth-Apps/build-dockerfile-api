<?php

namespace App\ManifestHelpers;

class AddExternalSecret
{
    public static function run(ManifestCollection $manifestCollection): void
    {
        $externalSecretArray = self::getBaseYaml(
            $manifestCollection->appName,
            $manifestCollection->namespace,
            $manifestCollection->getSecretPath(),
            $manifestCollection->branch,
            $manifestCollection->buildNumber
        );

        // Mount external secret to deployment
        $deploymentArray = $manifestCollection->getManifestArray('deployment');
        $deploymentArray['spec']['template']['spec']['containers'] = array_map(function ($container) use ($manifestCollection) {
            $container['envFrom'] = [
                [
                    'secretRef' => [
                        'name' => "{$manifestCollection->appName}-secret",
                    ],
                ],
            ];

            return $container;
        }, $deploymentArray['spec']['template']['spec']['containers']);
        $manifestCollection->addManifest('deployment', $deploymentArray);

        // Mount external secret to each CronJob in the manifest
        if ($manifestCollection->needsCronJob()) {
            $cronJobs = $manifestCollection->getManifestNamesByKind('CronJob');
            foreach ($cronJobs as $cronJobName) {
                $cronJobArray = $manifestCollection->getManifestArray($cronJobName);
                $cronJobArray['spec']['jobTemplate']['spec']['template']['spec']['containers'] = array_map(function ($container) use ($manifestCollection) {
                    $container['envFrom'] = [
                        [
                            'secretRef' => [
                                'name' => "{$manifestCollection->appName}-secret",
                                'optional' => false,
                            ],
                        ],
                    ];

                    return $container;
                }, $cronJobArray['spec']['jobTemplate']['spec']['template']['spec']['containers']
                );
                $manifestCollection->addManifest($cronJobName, $cronJobArray);
            }
        }

        $manifestCollection->addManifest('external-secret', $externalSecretArray);
    }

    private static function getBaseYaml(string $appName, string $namespace, string $secretPath, string $branch, string $buildNumber)
    {
        return [
            'apiVersion' => 'external-secrets.io/v1beta1',
            'kind' => 'ExternalSecret',
            'metadata' => [
                'name' => "{$appName}-external-secret",
                'namespace' => "{$namespace}",
                'labels' => [
                    'ua.edu/origin' => 'UA-Deploy-API',
                ],
                'annotations' => [
                    'refresh-timestamp' => "{$branch}-{$buildNumber}",
                ],
            ],
            'spec' => [
                'refreshInterval' => '1h',
                'secretStoreRef' => [
                    'kind' => 'ClusterSecretStore',
                    'name' => 'vault-backend',
                ],
                'dataFrom' => [
                    [
                        'extract' => [
                            'key' => "{$secretPath}",
                        ],
                    ],
                ],
                'target' => [
                    'name' => "{$appName}-secret",
                ],
            ],
        ];
    }
}

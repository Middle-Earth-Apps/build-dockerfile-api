<?php

namespace App\ManifestHelpers;

class AddVolumeMounts
{
    public static function run(ManifestCollection $manifestCollection): void
    {
        $deploymentArray = $manifestCollection->getManifestArray('deployment');
        $volumes = $manifestCollection->getVolumesArray();

        // If no volumes are defined, return
        if (empty($volumes)) {
            return;
        }

        $deploymentArray['spec']['template']['spec']['volumes'] = [];

        // Add security context to deployment
        $deploymentArray['spec']['template']['spec']['securityContext'] = [
            'fsGroup' => 2000,
        ];

        // Add volumes to deployment - skip if claim is absent
        foreach ($volumes as $index => $volume) {
            if (empty($volume['claim'])) {
                continue;
            }
            $name = "volume-{$index}";
            $volumeYamlArray = self::getVolumeYamlArray($name, $volume['claim']);
            array_push($deploymentArray['spec']['template']['spec']['volumes'], $volumeYamlArray);
        }

        // Mount volumes to containers
        $containers = $deploymentArray['spec']['template']['spec']['containers'];
        foreach ($containers as $containerIndex => $container) {
            $container['volumeMounts'] = [];
            foreach ($volumes as $index => $volume) {
                if (empty($volume['claim'])) {
                    continue;
                }
                $name = "volume-{$index}";
                $volumeMountYamlArray = [
                    'name' => $name,
                    'mountPath' => ! empty($volume['mountPath']) ? $volume['mountPath'] : '/var/www/html/storage/app',
                ];

                // Add subPath if included
                if (isset($volume['subPath'])) {
                    $volumeMountYamlArray['subPath'] = $volume['subPath'];
                }

                array_push($container['volumeMounts'], $volumeMountYamlArray);
            }
            // Update container in deployment array
            $deploymentArray['spec']['template']['spec']['containers'][$containerIndex] = $container;
        }

        $manifestCollection->addManifest('deployment', $deploymentArray);

        // ---- Add volumes to CronJobs ---
        // Filter manifests to get keys that contain 'CronJob'
        $cronJobArray = collect($manifestCollection->manifests)->filter(function ($value, $key) {
            return str_contains($key, 'CronJob');
        });

        // Loop through each CronJob and add volumes
        foreach ($cronJobArray as $cronJobKey => $cronJob) {
            $cronJob['spec']['jobTemplate']['spec']['template']['spec']['volumes'] = [];

            // Add security context to CronJob
            $cronJob['spec']['jobTemplate']['spec']['template']['spec']['securityContext'] = [
                'fsGroup' => 2000,
            ];

            // Add volumes to CronJob
            foreach ($volumes as $index => $volume) {
                if (empty($volume['claim'])) {
                    continue;
                }
                $name = "volume-{$index}";
                $volumeYamlArray = self::getVolumeYamlArray($name, $volume['claim']);
                array_push($cronJob['spec']['jobTemplate']['spec']['template']['spec']['volumes'], $volumeYamlArray);
            }

            // Mount volumes to containers in CronJob
            $containers = $cronJob['spec']['jobTemplate']['spec']['template']['spec']['containers'];
            foreach ($containers as $containerIndex => $container) {
                $container['volumeMounts'] = [];
                foreach ($volumes as $index => $volume) {
                    if (empty($volume['claim'])) {
                        continue;
                    }
                    $name = "volume-{$index}";
                    $volumeMountYamlArray = [
                        'name' => $name,
                        'mountPath' => ! empty($volume['mountPath']) ? $volume['mountPath'] : '/var/www/html/storage/app',
                    ];

                    // Add subPath if included
                    if (isset($volume['subPath'])) {
                        $volumeMountYamlArray['subPath'] = $volume['subPath'];
                    }

                    array_push($container['volumeMounts'], $volumeMountYamlArray);
                }
                // Update container in CronJob array
                $cronJob['spec']['jobTemplate']['spec']['template']['spec']['containers'][$containerIndex] = $container;
            }

            $manifestCollection->addManifest($cronJobKey, $cronJob);
        }
    }

    private static function getVolumeYamlArray(string $volumeName, string $claimName): array
    {
        return [
            'name' => "{$volumeName}",
            'persistentVolumeClaim' => [
                'claimName' => "{$claimName}",
            ],
        ];
    }
}

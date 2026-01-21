<?php

namespace App\ManifestHelpers;

use Illuminate\Support\Facades\Log;

class AddCronJob
{
    public static function run(ManifestCollection $manifestCollection): void
    {
        $deployPlan = $manifestCollection->deployPlan;
        $branch = $manifestCollection->branch;
        $cronjobs = $deployPlan['cron'] ?? [];

        foreach ($cronjobs as $cronjob) {
            // Ensure prod_only is treated consistently as a boolean
            $prodOnly = filter_var($cronjob['prod_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Skip cron job creation if it's marked as prod_only and the branch is not prod
            if ($prodOnly && $branch !== 'prod') {
                Log::info("Skipping cron job creation for {$cronjob['name']} as it is prod only and the current branch is {$branch}.");

                continue;
            }

            // Generate the cronjob manifest and add it to the collection with a unique key
            $cronjobManifest = self::getCronJobYamlArray($manifestCollection, $cronjob);
            $manifestCollection->addManifest(
                name: "CronJob-{$cronjob['name']}",  // Set a unique key for each CronJob
                manifest: $cronjobManifest
            );
        }
    }

    private static function getCronJobYamlArray(ManifestCollection $manifestCollection, array $cronjob): array
    {
        $memoryLimit = $manifestCollection->getMemoryLimit($cronjob['resources'] ?? []);
        $memoryRequest = $manifestCollection->getMemoryRequest($cronjob['resources'] ?? []);

        // Parse the command, handling quoted strings as single arguments
        $command = preg_split('/\s+(?=(?:[^\'"]|\'[^\']*\'|"[^"]*")*$)/', $cronjob['command']);
        $command = array_map(function ($item) {
            return trim($item, "'\""); // Remove surrounding quotes
        }, $command);

        $schedule = $cronjob['schedule'] ?? '0 0 * * *';

        return [
            'apiVersion' => 'batch/v1',
            'kind' => 'CronJob',
            'metadata' => [
                'name' => $manifestCollection->getAbbreviatedAppName().'-'.$cronjob['name'],
                'namespace' => "{$manifestCollection->getNamespace()}",
                'labels' => [
                    'app.kubernetes.io/name' => $manifestCollection->getAppName(),
                ],
            ],
            'spec' => [
                'schedule' => $schedule,
                'jobTemplate' => [
                    'spec' => [
                        'template' => [
                            'spec' => [
                                'containers' => [
                                    [
                                        'name' => 'cron',
                                        'image' => $manifestCollection->getImageNameAndTag(),
                                        'command' => $command,
                                        'resources' => [
                                            'requests' => [
                                                'cpu' => '200m',
                                                'memory' => $memoryRequest,
                                            ],
                                            'limits' => [
                                                'memory' => $memoryLimit,
                                            ],
                                        ],
                                    ],
                                ],
                                'imagePullSecrets' => [
                                    ['name' => 'dockerhub'],
                                ],
                                'restartPolicy' => 'OnFailure',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

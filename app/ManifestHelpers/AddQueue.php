<?php

namespace App\ManifestHelpers;

class AddQueue
{
    public static function run(ManifestCollection $manifestCollection, $queue): void
    {
        $deploymentArray = $manifestCollection->getManifestArray('deployment');

        if (is_array($queue)) {
            $index = 1;
            foreach ($queue as $config) {
                if (! empty($config)) {
                    $name = "queue{$index}";
                    $queueArray = self::getQueueYamlArray($manifestCollection, $config, $name);
                    $deploymentArray['spec']['template']['spec']['containers'][] = $queueArray;
                    $index++;
                }
            }
        } else {
            $queueArray = self::getQueueYamlArray($manifestCollection);
            $deploymentArray['spec']['template']['spec']['containers'][] = $queueArray;
        }

        $manifestCollection->addManifest('deployment', $deploymentArray);
    }

    private static function getQueueYamlArray(ManifestCollection $manifestCollection, array $queue = [], string $name = 'queue'): array
    {
        $memoryLimit = $manifestCollection->getMemoryLimit($queue['resources'] ?? []);
        $memoryRequest = $manifestCollection->getMemoryRequest($queue['resources'] ?? []);

        $queueName = $queue['queue_name'] ?? 'high,default';
        $defaultTimeout = 300;
        $defaultTries = 3;

        // Normalize and validate timeout
        $timeoutRaw = $queue['timeout'] ?? $defaultTimeout;
        $timeout = ctype_digit((string) $timeoutRaw) ? (string) (int) $timeoutRaw : (string) $defaultTimeout;

        // Normalize and validate tries
        $triesRaw = $queue['tries'] ?? $defaultTries;
        $tries = ctype_digit((string) $triesRaw) ? (string) (int) $triesRaw : (string) $defaultTries;

        return [
            'name' => $name,
            'image' => "{$manifestCollection->getImageNameAndTag()}",
            'imagePullPolicy' => 'Always',
            'args' => [
                'artisan',
                'queue:work',
                "--queue={$queueName}",
                '--sleep=3',
                "--timeout={$timeout}",
                "--tries={$tries}",
            ],
            'command' => [
                'php',
            ],
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
    }
}

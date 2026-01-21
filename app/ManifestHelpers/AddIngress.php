<?php

namespace App\ManifestHelpers;

class AddIngress
{
    public static function run(ManifestCollection $manifestCollection): void
    {
        $ingressArray = self::getIngressYamlArray($manifestCollection);

        // Add vanity URL rule if requested in deploy plan
        if ($manifestCollection->getIngressVanityUrl()) {
            array_push($ingressArray['spec']['rules'], self::getVanityUrlRule($manifestCollection));
        }

        $manifestCollection->addManifest('ingress', $ingressArray);
    }

    private static function getIngressYamlArray(ManifestCollection $manifestCollection): array
    {
        return [
            'apiVersion' => 'networking.k8s.io/v1',
            'kind' => 'Ingress',
            'metadata' => [
                'name' => "{$manifestCollection->getAppName()}-ingress",
                'namespace' => "{$manifestCollection->getNamespace()}",
                'annotations' => [
                    'nginx.ingress.kubernetes.io/affinity' => 'cookie',
                    'nginx.ingress.kubernetes.io/proxy-body-size' => ($manifestCollection->getMaxFileSize() + 10).'M',
                    'nginx.ingress.kubernetes.io/proxy-connect-timeout' => '600',
                    'nginx.ingress.kubernetes.io/proxy-read-timeout' => '600',
                    'nginx.ingress.kubernetes.io/proxy-send-timeout' => '600',
                    'nginx.ingress.kubernetes.io/proxy-buffer-size' => '16k',
                    'nginx.ingress.kubernetes.io/proxy-buffers-number' => '8',
                    'nginx.ingress.kubernetes.io/proxy-busy-buffers-size' => '16k',
                ],
                'labels' => [
                    'ua.edu/origin' => 'UA-Deploy-API',
                ],
            ],
            'spec' => [
                'ingressClassName' => 'nginx',
                'rules' => [
                    [
                        'host' => "{$manifestCollection->getIngressUrl()}",
                        'http' => [
                            'paths' => [
                                [
                                    'path' => '/',
                                    'pathType' => 'Prefix',
                                    'backend' => [
                                        'service' => [
                                            'name' => "{$manifestCollection->getAppName()}-svc",
                                            'port' => [
                                                'number' => 80,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function getVanityUrlRule(ManifestCollection $manifestCollection): array
    {
        return [
            'host' => "{$manifestCollection->getIngressVanityUrl()}",
            'http' => [
                'paths' => [
                    [
                        'path' => '/',
                        'pathType' => 'Prefix',
                        'backend' => [
                            'service' => [
                                'name' => "{$manifestCollection->getAppName()}-svc",
                                'port' => [
                                    'number' => 80,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

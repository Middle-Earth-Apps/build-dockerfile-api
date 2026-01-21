<?php

namespace App\ManifestHelpers;

class InitBaseArgoApp
{
    public static function run(string $appName, string $branch): array
    {
        return self::getArgoAppYamlArray($appName, $branch);
    }

    private static function getArgoAppYamlArray(string $appName, string $branch): array
    {
        return [
            'apiVersion' => 'argoproj.io/v1alpha1',
            'kind' => 'Application',
            'metadata' => [
                'name' => "{$appName}-{$branch}",
                'namespace' => 'argocd',
                'finalizers' => [
                    'resources-finalizer.argocd.argoproj.io',
                ],
            ],
            'spec' => [
                'destination' => [
                    'server' => 'https://kubernetes.default.svc',
                ],
                'project' => 'default',
                'source' => [
                    'path' => "applications/{$branch}/{$appName}",
                    'repoURL' => 'https://github.com/OIT-GITOPS/test-cluster.git',
                    'targetRevision' => 'main',
                ],
                'syncPolicy' => [
                    'automated' => [
                        'prune' => true,
                        'selfHeal' => true,
                    ],
                ],
            ],
        ];
    }
}

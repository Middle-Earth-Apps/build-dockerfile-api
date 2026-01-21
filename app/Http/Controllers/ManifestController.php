<?php

namespace App\Http\Controllers;

use App\ManifestHelpers\AddCronJob;
use App\ManifestHelpers\AddDeployment;
use App\ManifestHelpers\AddExternalSecret;
use App\ManifestHelpers\AddExtraContainers;
use App\ManifestHelpers\AddIngress;
use App\ManifestHelpers\AddQueue;
use App\ManifestHelpers\AddService;
use App\ManifestHelpers\AddVolumeMounts;
use App\ManifestHelpers\Enums\LaravelDefaultValues;
use App\ManifestHelpers\ManifestCollection;
use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function index(Request $request)
    {
        // *********** Testing */
        // TODO: Validate inputs!!
        $appName = $request->get('appName');
        $buildNumber = $request->get('buildNumber'); // used to calculate the image tag
        $deployPlan = $request->get('server');
        $environment = $request->get('branch');
        $image = $request->get('image') ?? [];

        $defaultValues = new LaravelDefaultValues;

        $manifestCollection = new ManifestCollection($appName, $environment, $deployPlan, $image, $buildNumber, $defaultValues);

        // Deployment
        AddDeployment::run($manifestCollection);

        // Queue
        if ($manifestCollection->needsQueue()) {
            AddQueue::run($manifestCollection, $deployPlan['queue']);
        }

        // Extra Containers
        if ($manifestCollection->needsExtraContainers()) {
            AddExtraContainers::run($manifestCollection, $deployPlan['extra_containers']);
        }

        // CronJob
        if ($manifestCollection->needsCronJob()) {
            AddCronJob::run($manifestCollection);
        }

        // Service
        if ($manifestCollection->needsService()) {
            AddService::run($manifestCollection);
        }

        // Ingress
        if ($manifestCollection->needsIngress()) {
            AddIngress::run($manifestCollection);
        }

        // Volumes
        if (! empty($deployPlan['volumes'])) {
            AddVolumeMounts::run($manifestCollection);
        }

        // External Secret
        if (($deployPlan['external_secret'] ?? 'true') == 'true') {
            AddExternalSecret::run($manifestCollection);
        }

        return response($manifestCollection->generateAllYaml())->header('Content-Type', 'application/x-yaml');
    }
}

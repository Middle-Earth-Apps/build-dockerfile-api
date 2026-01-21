<?php

namespace App\Http\Controllers;

use App\ManifestHelpers\InitBaseArgoApp;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;

class ArgoAppManifestController extends Controller
{
    public function index(Request $request)
    {
        // TODO: validate inputs
        $appName = $request->get('appName');
        $branch = $request->get('branch');

        $argoAppArray = InitBaseArgoApp::run(appName: $appName, branch: $branch);

        $yaml = Yaml::dump($argoAppArray, 100, 2);

        return response($yaml)->header('Content-Type', 'application/x-yaml');
    }
}

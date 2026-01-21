<?php

use App\Http\Controllers\ArgoAppManifestController;
use App\Http\Controllers\DockerDevImageBuilderController;
use App\Http\Controllers\DockerImageBuilderController;
use App\Http\Controllers\ManifestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.auth'])->group(function () {
    Route::post('/argo-app', [ArgoAppManifestController::class, 'index']);
    Route::post('/docker/build', [DockerImageBuilderController::class, 'index']);
    Route::post('/docker/build-dev', [DockerDevImageBuilderController::class, 'index']);
    Route::post('/manifests', [ManifestController::class, 'index']);
});

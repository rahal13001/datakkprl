<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $build = max(0, (int) $request->query('build', 0));
        $latestBuild = config('mobile.android.latest_build');
        $minimumBuild = config('mobile.android.minimum_build');

        return response()->json(['data' => [
            'api_version' => config('mobile.api_version'),
            'platform' => 'android',
            'latest_version' => config('mobile.android.latest_version'),
            'latest_build' => $latestBuild,
            'minimum_version' => config('mobile.android.minimum_version'),
            'minimum_build' => $minimumBuild,
            'update_available' => $build > 0 && $build < $latestBuild,
            'update_required' => $build > 0 && $build < $minimumBuild,
            'distribution_url' => config('mobile.android.distribution_url'),
            'maintenance' => [
                'enabled' => config('mobile.maintenance.enabled'),
                'message' => config('mobile.maintenance.message'),
            ],
        ]]);
    }
}

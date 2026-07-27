<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\MobileCapabilityService;
use App\Services\MobileTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(
        Request $request,
        MobileTransformer $transformer,
        MobileCapabilityService $capabilities,
    ): JsonResponse {
        return response()->json(['data' => [
            'user' => $transformer->user($request->user()),
            'capabilities' => $capabilities->for($request->user()),
        ]]);
    }

    public function capabilities(Request $request, MobileCapabilityService $capabilities): JsonResponse
    {
        return response()->json(['data' => $capabilities->for($request->user())]);
    }

    public function upsertDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'installation_id' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:android,ios'],
            'device_name' => ['required', 'string', 'max:255'],
            'app_version' => ['required', 'string', 'max:50'],
            'build_number' => ['required', 'integer', 'min:1'],
            'push_registration' => ['nullable', 'string', 'max:8192'],
            'notification_permission' => ['required', 'in:prompt,prompt-with-rationale,granted,denied'],
        ]);

        UserDevice::query()
            ->where('installation_id', $data['installation_id'])
            ->where('user_id', '!=', $request->user()->id)
            ->get()
            ->each(function (UserDevice $device): void {
                $device->accessToken?->delete();
                $device->update([
                    'disabled_at' => now(),
                    'personal_access_token_id' => null,
                    'push_registration' => null,
                ]);
            });

        if (filled($data['push_registration'] ?? null)) {
            UserDevice::query()
                ->where('push_registration_hash', hash('sha256', $data['push_registration']))
                ->where(function ($query) use ($request, $data): void {
                    $query->where('user_id', '!=', $request->user()->id)
                        ->orWhere('installation_id', '!=', $data['installation_id']);
                })
                ->update([
                    'disabled_at' => now(),
                    'personal_access_token_id' => null,
                    'push_registration_encrypted' => null,
                    'push_registration_hash' => null,
                ]);
        }

        $token = $request->user()->currentAccessToken();
        $device = UserDevice::updateOrCreate(
            ['user_id' => $request->user()->id, 'installation_id' => $data['installation_id']],
            array_merge($data, [
                'personal_access_token_id' => $token?->id,
                'last_registered_at' => filled($data['push_registration'] ?? null) ? now() : null,
                'last_seen_at' => now(),
                'disabled_at' => null,
            ]),
        );

        return response()->json(['data' => [
            'id' => $device->id,
            'platform' => $device->platform,
            'notification_permission' => $device->notification_permission,
            'registered' => filled($device->push_registration),
            'last_seen_at' => $device->last_seen_at?->toISOString(),
        ]]);
    }

    public function disableDevice(Request $request): JsonResponse
    {
        $data = $request->validate(['installation_id' => ['required', 'string', 'max:255']]);
        $devices = $request->user()->devices()
            ->where('installation_id', $request->input('installation_id'))
            ->get();

        $devices->each(function (UserDevice $device): void {
            $device->accessToken?->delete();
        });
        $request->user()->devices()
            ->where('installation_id', $data['installation_id'])
            ->update([
                'disabled_at' => now(),
                'personal_access_token_id' => null,
                'push_registration_encrypted' => null,
                'push_registration_hash' => null,
            ]);

        return response()->json(['data' => ['disabled' => true]]);
    }
}

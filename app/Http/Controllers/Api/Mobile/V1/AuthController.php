<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Actions\Mobile\AuthenticateSummaryUser;
use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\MobileCapabilityService;
use App\Services\MobileTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(
        Request $request,
        AuthenticateSummaryUser $authenticate,
        MobileCapabilityService $capabilities,
        MobileTransformer $transformer,
    ): JsonResponse {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'installation_id' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:android,ios'],
            'app_version' => ['required', 'string', 'max:50'],
            'build_number' => ['required', 'integer', 'min:1'],
            'notification_permission' => ['nullable', 'in:prompt,prompt-with-rationale,granted,denied'],
        ]);

        $user = $authenticate->handle(Str::lower($data['email']), $data['password']);

        if (! $user->status) {
            return response()->json([
                'message' => 'This account is not active.',
                'code' => 'account_inactive',
                'request_id' => $request->attributes->get('request_id'),
            ], 403);
        }

        if (! $capabilities->hasMobileAccess($user)) {
            return response()->json([
                'message' => 'A ServiceKKPRL role has not been assigned to this account.',
                'code' => 'mobile_access_not_assigned',
                'request_id' => $request->attributes->get('request_id'),
            ], 403);
        }

        $this->retireInstallation($data['installation_id'], $user->id);

        $expiresAt = now()->addDays(config('mobile.token_expiration_days'));
        $token = $user->createToken(
            "servicekkprl:{$data['installation_id']}",
            ['mobile'],
            $expiresAt,
        );

        $device = UserDevice::updateOrCreate(
            ['user_id' => $user->id, 'installation_id' => $data['installation_id']],
            [
                'personal_access_token_id' => $token->accessToken->id,
                'platform' => $data['platform'],
                'device_name' => $data['device_name'],
                'app_version' => $data['app_version'],
                'build_number' => $data['build_number'],
                'notification_permission' => $data['notification_permission'] ?? 'prompt',
                'last_seen_at' => now(),
                'disabled_at' => null,
            ],
        );

        return response()->json(['data' => [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
            'user' => $transformer->user($user),
            'capabilities' => $capabilities->for($user),
            'device_id' => $device->id,
        ]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('personal_access_token_id', $token?->id)
            ->update(['disabled_at' => now(), 'personal_access_token_id' => null]);

        $token?->delete();

        return response()->json(['data' => ['logged_out' => true]]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->devices()->update([
            'disabled_at' => now(),
            'personal_access_token_id' => null,
        ]);
        $request->user()->tokens()->where('name', 'like', 'servicekkprl:%')->delete();

        return response()->json(['data' => ['logged_out' => true, 'all_devices' => true]]);
    }

    private function retireInstallation(string $installationId, int $currentUserId): void
    {
        UserDevice::query()
            ->where('installation_id', $installationId)
            ->get()
            ->each(function (UserDevice $device) use ($currentUserId): void {
                $device->accessToken?->delete();
                $device->update([
                    'disabled_at' => now(),
                    'personal_access_token_id' => null,
                    ...($device->user_id !== $currentUserId ? [
                        'push_registration' => null,
                    ] : []),
                ]);
            });
    }
}

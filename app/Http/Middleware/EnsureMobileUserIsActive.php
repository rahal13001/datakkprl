<?php

namespace App\Http\Middleware;

use App\Models\UserDevice;
use App\Services\MobileCapabilityService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileUserIsActive
{
    public function __construct(private readonly MobileCapabilityService $capabilities) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->status) {
            $this->revokeCurrentInstallation($request);

            return response()->json([
                'message' => 'This account is not active.',
                'code' => 'account_inactive',
                'request_id' => $request->attributes->get('request_id'),
            ], 403);
        }

        if (! $this->capabilities->hasMobileAccess($request->user())) {
            $this->revokeCurrentInstallation($request);

            return response()->json([
                'message' => 'A ServiceKKPRL role has not been assigned to this account.',
                'code' => 'mobile_access_not_assigned',
                'request_id' => $request->attributes->get('request_id'),
            ], 403);
        }

        return $next($request);
    }

    private function revokeCurrentInstallation(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return;
        }

        UserDevice::query()
            ->where('personal_access_token_id', $token->id)
            ->update([
                'disabled_at' => now(),
                'personal_access_token_id' => null,
            ]);
        $token->delete();
    }
}

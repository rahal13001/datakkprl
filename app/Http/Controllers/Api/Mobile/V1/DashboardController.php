<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\MobileTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('viewAny', Client::class);
        $base = Client::query();

        $recent = Client::query()
            ->with(['service', 'consultationLocation', 'schedules'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Client $client) => $transformer->clientSummary($client));

        return response()->json(['data' => [
            'counts' => [
                'total' => (clone $base)->count(),
                'waiting' => (clone $base)->where('status', 'waiting')->count(),
                'scheduled' => (clone $base)->where('status', 'scheduled')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'mine' => (clone $base)->whereHas(
                    'assignments',
                    fn ($query) => $query->where('user_id', $request->user()->id),
                )->count(),
                'unread_notifications' => $request->user()->unreadNotifications()->count(),
            ],
            'recent_requests' => $recent,
        ]]);
    }
}

<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Schedule;
use App\Services\MobileTransformer;
use App\Support\EnsuresMobileVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use EnsuresMobileVersion;

    public function store(Request $request, Client $client, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('update', $client);
        $data = $this->validateSchedule($request);
        $schedule = $client->schedules()->create($data);

        return response()->json(['data' => $transformer->schedule($schedule)], 201);
    }

    public function update(
        Request $request,
        Client $client,
        Schedule $schedule,
        MobileTransformer $transformer,
    ): JsonResponse {
        $this->authorize('update', $client);
        abort_unless($schedule->client_id === $client->id, 404);
        $this->ensureVersion($request, $schedule);

        $data = $this->validateSchedule($request);
        unset($data['version']);
        $schedule->update($data);

        return response()->json(['data' => $transformer->schedule($schedule)]);
    }

    private function validateSchedule(Request $request): array
    {
        return $request->validate([
            'version' => ['sometimes', 'string'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_online' => ['required', 'boolean'],
            'meeting_link' => ['nullable', 'required_if:is_online,true', 'url', 'max:2048'],
        ]);
    }
}

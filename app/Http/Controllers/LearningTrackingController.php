<?php

namespace App\Http\Controllers;

use App\Models\LearningActivityLog;
use App\Services\LearningTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LearningTrackingController extends Controller
{
    public const ACTIVITY_TYPES = [
        'open_material', 'scroll_25', 'scroll_50', 'scroll_75', 'scroll_100',
        'play_video', 'pause_video', 'complete_material', 'download_file',
        'leave_page', 'heartbeat',
    ];

    public function __construct(private readonly LearningTrackingService $tracking) {}

    public function storeAccess(Request $request): JsonResponse
    {
        $data = $request->validate([
            'material_id' => ['required', 'integer'],
            'material_key' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:150'],
            'institution' => ['required', 'string', 'max:200'],
            'access_purpose' => ['required', 'string', 'max:255'],
            'browser_uuid' => ['nullable', 'uuid'],
        ]);

        [$access, $session] = $this->tracking->createAccess($data, $request);

        return response()->json([
            'access_uuid' => $access->access_uuid,
            'session_id' => $session->getKey(),
            'name' => $access->name,
            'institution' => $access->institution,
            'material_key' => $access->material_key,
            'material_title' => $access->material_title,
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        $data = $request->validate([
            'access_uuid' => ['required', 'uuid'],
            'material_key' => ['required', 'string', 'max:255'],
        ]);
        $access = $this->tracking->access($data['access_uuid'], $data['material_key']);

        return response()->json($access->only('access_uuid', 'name', 'institution', 'material_key', 'material_title'));
    }

    public function startSession(Request $request): JsonResponse
    {
        $data = $this->validateAccess($request);
        $access = $this->tracking->access($data['access_uuid'], $data['material_key']);
        $session = $this->tracking->startSession($access, $request);

        return response()->json(['session_id' => $session->getKey()]);
    }

    public function endSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'access_uuid' => ['required', 'uuid'],
            'material_key' => ['required', 'string', 'max:255'],
            'session_id' => ['required', 'integer'],
        ]);
        $access = $this->tracking->access($data['access_uuid'], $data['material_key']);
        $session = $access->sessions()->find($data['session_id']);

        throw_unless($session, ValidationException::withMessages(['session_id' => 'Sesi tidak valid.']));

        if (! $session->ended_at) {
            $endedAt = now();
            $session->update([
                'ended_at' => $endedAt,
                'duration_seconds' => (int) max(0, $session->started_at->diffInSeconds($endedAt)),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function activity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'access_uuid' => ['required', 'uuid'],
            'session_id' => ['nullable', 'integer'],
            'material_key' => ['required', 'string', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'activity_type' => ['required', Rule::in(self::ACTIVITY_TYPES)],
            'progress_percent' => ['nullable', 'integer', 'between:0,100'],
            'metadata' => ['nullable', 'array', 'max:10'],
        ]);
        $access = $this->tracking->access($data['access_uuid'], $data['material_key']);
        $sessionId = null;

        if (! empty($data['session_id'])) {
            $sessionId = $access->sessions()->whereKey($data['session_id'])->value('id');
            throw_unless($sessionId, ValidationException::withMessages(['session_id' => 'Sesi tidak valid.']));
        }

        $activity = LearningActivityLog::create([
            'learning_material_access_id' => $access->getKey(),
            'learning_session_id' => $sessionId,
            'material_type' => $access->material_type,
            'material_id' => $access->material_id,
            'material_key' => $access->material_key,
            'material_title' => $access->material_title,
            'page_url' => $data['page_url'] ?? null,
            'activity_type' => $data['activity_type'],
            'progress_percent' => $data['progress_percent'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'occurred_at' => now(),
        ]);

        return response()->json(['id' => $activity->getKey()], 201);
    }

    private function validateAccess(Request $request): array
    {
        return $request->validate([
            'access_uuid' => ['required', 'uuid'],
            'material_key' => ['required', 'string', 'max:255'],
        ]);
    }
}

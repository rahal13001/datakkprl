<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Actions\Mobile\CreateAssignments;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Client;
use App\Models\User;
use App\Services\MobileTransformer;
use App\Support\EnsuresMobileVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    use EnsuresMobileVersion;

    public function store(
        Request $request,
        Client $client,
        CreateAssignments $createAssignments,
        MobileTransformer $transformer,
    ): JsonResponse {
        $this->authorize('create', Assignment::class);
        $this->authorize('view', $client);
        $data = $request->validate([
            'schedule_ids' => ['required', 'array', 'min:1'],
            'schedule_ids.*' => ['required', 'integer', 'distinct'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'distinct'],
            'status' => ['required', 'in:scheduled,hadir,izin_mendadak'],
        ]);

        [$assignments, $warnings] = $createAssignments->handle(
            $client,
            $data['schedule_ids'],
            $data['user_ids'],
            $data['status'],
        );
        $assignments->load('user');

        return response()->json([
            'data' => $assignments->map(fn (Assignment $assignment) => $transformer->assignment($assignment)),
            'meta' => ['warnings' => $warnings],
        ], 201);
    }

    public function update(
        Request $request,
        Client $client,
        Assignment $assignment,
        MobileTransformer $transformer,
    ): JsonResponse {
        $this->authorize('update', $assignment);
        abort_unless($assignment->schedule?->client_id === $client->id, 404);
        $this->ensureVersion($request, $assignment);

        $data = $request->validate([
            'version' => ['required', 'string'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'in:scheduled,hadir,izin_mendadak'],
        ]);
        unset($data['version']);

        if (isset($data['user_id'])) {
            abort_unless(User::whereKey($data['user_id'])->where('status', true)->exists(), 422);
        }

        $assignment->update($data);
        $assignment->load('user');

        return response()->json(['data' => $transformer->assignment($assignment)]);
    }
}

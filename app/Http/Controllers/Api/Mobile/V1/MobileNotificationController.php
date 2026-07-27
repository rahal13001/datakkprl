<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class MobileNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cursor' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $paginator = $request->user()->notifications()
            ->latest()
            ->cursorPaginate($data['per_page'] ?? 20);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (DatabaseNotification $notification) => $this->data($notification)),
            'meta' => [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            'count' => $request->user()->unreadNotifications()->count(),
        ]]);
    }

    public function show(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();

        return response()->json(['data' => $this->data($record)]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return response()->json(['data' => $this->data($record->refresh())]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['data' => ['read' => true]]);
    }

    private function data(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->data['event_type'] ?? class_basename($notification->type),
            'title' => $notification->data['title'] ?? 'ServiceKKPRL update',
            'body' => $notification->data['body'] ?? null,
            'ticket_number' => $notification->data['ticket_number'] ?? null,
            'route' => $notification->data['route'] ?? null,
            'subject_type' => $notification->data['subject_type'] ?? null,
            'subject_id' => $notification->data['subject_id'] ?? null,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
}

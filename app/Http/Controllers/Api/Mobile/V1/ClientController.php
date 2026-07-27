<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\MobileTransformer;
use App\Support\EnsuresMobileVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    use EnsuresMobileVersion;

    public function index(Request $request, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('viewAny', Client::class);
        $request->validate([
            'scope' => ['nullable', 'in:all,mine'],
            'status' => ['nullable', 'in:waiting,scheduled,completed'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'location_id' => ['nullable', 'integer', 'exists:consultation_locations,id'],
            'activity_type' => ['nullable', 'in:business,non_business'],
            'date_from' => ['nullable', 'date'],
            'date_until' => ['nullable', 'date', 'after_or_equal:date_from'],
            'ticket' => ['nullable', 'string', 'max:100'],
            'cursor' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Client::query()
            ->with(['service', 'consultationLocation', 'schedules'])
            ->when($request->input('scope') === 'mine', fn ($query) => $query->whereHas(
                'assignments',
                fn ($assignmentQuery) => $assignmentQuery->where('user_id', $request->user()->id),
            ))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $request->integer('service_id')))
            ->when($request->filled('location_id'), fn ($query) => $query->where('consultation_location_id', $request->integer('location_id')))
            ->when($request->filled('activity_type'), fn ($query) => $query->where('activity_type', $request->input('activity_type')))
            ->when($request->filled('ticket'), fn ($query) => $query->where('ticket_number', $request->input('ticket')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereHas(
                'schedules',
                fn ($scheduleQuery) => $scheduleQuery->whereDate('date', '>=', $request->input('date_from')),
            ))
            ->when($request->filled('date_until'), fn ($query) => $query->whereHas(
                'schedules',
                fn ($scheduleQuery) => $scheduleQuery->whereDate('date', '<=', $request->input('date_until')),
            ))
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $paginator = $query->cursorPaginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (Client $client) => $transformer->clientSummary($client)),
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'previous_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ]);
    }

    public function show(Client $client, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('view', $client);
        $client->load([
            'service',
            'consultationLocation',
            'schedules',
            'assignments.user',
            'consultationReports',
            'beritaAcara.attendees',
            'satisfactionSurvey',
        ]);

        return response()->json(['data' => $transformer->client($client)]);
    }

    public function update(Request $request, Client $client, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('update', $client);
        $this->ensureVersion($request, $client);

        $data = $request->validate([
            'version' => ['required', 'string'],
            'service_id' => ['sometimes', 'integer', 'exists:services,id'],
            'consultation_location_id' => ['sometimes', 'nullable', 'integer', 'exists:consultation_locations,id'],
            'status' => ['sometimes', Rule::in(['waiting', 'scheduled', 'completed'])],
            'booking_type' => ['sometimes', Rule::in(['personal', 'company'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'whatsapp' => ['sometimes', 'string', 'max:50'],
            'instance' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'activity_type' => ['sometimes', 'nullable', Rule::in(['business', 'non_business'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        unset($data['version']);
        $client->update($data);
        $client->load([
            'service', 'consultationLocation', 'schedules', 'assignments.user',
            'consultationReports', 'beritaAcara.attendees', 'satisfactionSurvey',
        ]);

        return response()->json(['data' => $transformer->client($client)]);
    }
}

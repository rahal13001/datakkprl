<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ConsultationReport;
use App\Services\MobileTransformer;
use App\Services\SafeRichText;
use App\Support\EnsuresMobileVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ConsultationReportController extends Controller
{
    use EnsuresMobileVersion;

    public function __construct(private readonly SafeRichText $richText) {}

    public function index(Client $client, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('viewAny', ConsultationReport::class);
        $this->authorize('view', $client);
        $reports = $client->consultationReports()->latest()->get();

        return response()->json([
            'data' => $reports->map(fn (ConsultationReport $report) => $transformer->report($report)),
        ]);
    }

    public function store(Request $request, Client $client, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('create', ConsultationReport::class);
        $this->authorize('view', $client);
        $data = $this->validated($request, true);
        $data['documentation'] = $this->storeDocumentation($request->file('documentation', []));
        $report = $client->consultationReports()->create($data);

        return response()->json(['data' => $transformer->report($report)], 201);
    }

    public function update(
        Request $request,
        Client $client,
        ConsultationReport $report,
        MobileTransformer $transformer,
    ): JsonResponse {
        $this->authorize('update', $report);
        abort_unless($report->client_id === $client->id, 404);
        $this->ensureVersion($request, $report);
        $data = $this->validated($request, false);
        unset($data['version']);

        if ($request->hasFile('documentation')) {
            $data['documentation'] = $this->storeDocumentation($request->file('documentation', []));
        }

        $report->update($data);

        return response()->json(['data' => $transformer->report($report)]);
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'version' => [$creating ? 'nullable' : 'required', 'string'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,completed'],
            'documentation' => [$creating ? 'required' : 'sometimes', 'array', 'min:1', 'max:3'],
            'documentation.*' => ['image', 'max:10240'],
        ]);
        $data['content'] = $this->richText->sanitize($data['content']);

        return $data;
    }

    private function storeDocumentation(array $files): array
    {
        return collect($files)
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file): string => $file->store('consultation-documentation', 'local'))
            ->values()
            ->all();
    }
}

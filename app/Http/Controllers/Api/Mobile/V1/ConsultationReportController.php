<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ConsultationReport;
use App\Services\MobileTransformer;
use App\Services\SafeRichText;
use App\Services\SignatureService;
use App\Support\EnsuresMobileVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class ConsultationReportController extends Controller
{
    use EnsuresMobileVersion;

    public function __construct(
        private readonly SafeRichText $richText,
        private readonly SignatureService $signatures,
    ) {}

    public function index(Client $client, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('viewAny', ConsultationReport::class);
        $this->authorize('view', $client);
        $reports = $client->consultationReports()->with('signer')->latest()->get();

        return response()->json([
            'data' => $reports->map(fn (ConsultationReport $report) => $transformer->report($report)),
        ]);
    }

    public function store(Request $request, Client $client, MobileTransformer $transformer): JsonResponse
    {
        $this->authorize('create', ConsultationReport::class);
        $this->authorize('view', $client);
        $data = $this->validated($request, true);
        $this->storeSignature($request, $data);
        $data['documentation'] = $this->storeDocumentation($request->file('documentation', []));
        $report = $client->consultationReports()->create($data);
        $report->load('signer');

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
        $previousSignature = $report->officer_signature;
        $signatureChanged = $this->storeSignature($request, $data);

        if ($request->hasFile('documentation')) {
            $data['documentation'] = $this->storeDocumentation($request->file('documentation', []));
        }

        $report->update($data);
        if ($signatureChanged && $previousSignature) {
            $this->signatures->delete($previousSignature);
        }
        $report->load('signer');

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
            'signature' => $this->signatureRules(),
        ]);
        $data['content'] = $this->richText->sanitize($data['content']);

        return $data;
    }

    private function storeSignature(Request $request, array &$data): bool
    {
        $signature = Arr::pull($data, 'signature');
        if (! $signature) {
            return false;
        }

        $data['officer_signature'] = $this->signatures->store($signature);
        $data['signed_by'] = $request->user()->id;
        $data['signed_at'] = now();

        return true;
    }

    private function signatureRules(): array
    {
        return [
            'nullable',
            'string',
            'max:3000000',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! str_starts_with($value, 'data:image/png;base64,')) {
                    $fail("The {$attribute} field must be a PNG signature.");

                    return;
                }

                $decoded = base64_decode(substr($value, strlen('data:image/png;base64,')), true);
                if ($decoded === false || ! str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
                    $fail("The {$attribute} field must contain a valid PNG signature.");
                }
            },
        ];
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

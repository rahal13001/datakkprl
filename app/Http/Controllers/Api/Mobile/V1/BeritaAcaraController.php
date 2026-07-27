<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcara;
use App\Models\Client;
use App\Services\MobileTransformer;
use App\Services\SafeRichText;
use App\Services\SignatureService;
use App\Support\EnsuresMobileVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BeritaAcaraController extends Controller
{
    use EnsuresMobileVersion;

    public function __construct(private readonly SafeRichText $richText) {}

    public function show(Client $client, MobileTransformer $transformer): JsonResponse
    {
        $record = $client->beritaAcara()->with('attendees')->firstOrFail();
        $this->authorize('view', $record);

        return response()->json(['data' => $transformer->beritaAcara($record)]);
    }

    public function store(
        Request $request,
        Client $client,
        SignatureService $signatures,
        MobileTransformer $transformer,
    ): JsonResponse {
        $this->authorize('create', BeritaAcara::class);
        abort_if($client->beritaAcara()->exists(), 409, 'A Berita Acara already exists for this request.');
        $data = $this->validated($request, false);
        $record = $this->persist($client, null, $data, $request, $signatures);

        return response()->json(['data' => $transformer->beritaAcara($record)], 201);
    }

    public function update(
        Request $request,
        Client $client,
        BeritaAcara $beritaAcara,
        SignatureService $signatures,
        MobileTransformer $transformer,
    ): JsonResponse {
        $this->authorize('update', $beritaAcara);
        abort_unless($beritaAcara->client_id === $client->id, 404);
        $this->ensureVersion($request, $beritaAcara);
        $data = $this->validated($request, true);
        $record = $this->persist($client, $beritaAcara, $data, $request, $signatures);

        return response()->json(['data' => $transformer->beritaAcara($record)]);
    }

    public function attendanceLink(Client $client, BeritaAcara $beritaAcara): JsonResponse
    {
        $this->authorize('view', $beritaAcara);
        abort_unless($beritaAcara->client_id === $client->id, 404);

        return response()->json(['data' => [
            'url' => $beritaAcara->getAttendanceUrl(),
            'is_open' => (bool) $beritaAcara->attendance_is_open,
        ]]);
    }

    public function signingLinks(Client $client, BeritaAcara $beritaAcara): JsonResponse
    {
        $this->authorize('view', $beritaAcara);
        abort_unless($beritaAcara->client_id === $client->id, 404);

        return response()->json(['data' => $beritaAcara->attendees()
            ->get()
            ->map(fn ($attendee) => [
                'id' => $attendee->id,
                'name' => $attendee->nama,
                'url' => $attendee->getSigningUrl(),
                'confirmed_at' => $attendee->confirmed_at?->toISOString(),
            ])
            ->values()]);
    }

    private function validated(Request $request, bool $updating): array
    {
        $data = $request->validate([
            'version' => [$updating ? 'required' : 'nullable', 'string'],
            'nomor_berita_acara' => ['nullable', 'string', 'max:100'],
            'kbli' => ['nullable', 'string', 'max:50'],
            'tanggal_pelaksanaan' => ['required', 'date'],
            'lokasi_permohonan' => ['nullable', 'string'],
            'hasil_pendampingan' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,completed'],
            'attendance_is_open' => ['required', 'boolean'],
            'applicant_signature' => $this->signatureRules(),
            'map_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documentation_attachments' => ['nullable', 'array', 'max:6'],
            'documentation_attachments.*' => ['image', 'max:10240'],
            'other_attachments' => ['nullable', 'array', 'max:5'],
            'other_attachments.*' => ['file', 'max:10240'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.id' => ['nullable', 'integer'],
            'attendees.*.name' => ['required_with:attendees', 'string', 'max:255'],
            'attendees.*.position' => ['nullable', 'string', 'max:255'],
            'attendees.*.institution' => ['nullable', 'string', 'max:255'],
            'attendees.*.email' => ['nullable', 'email', 'max:255'],
            'attendees.*.phone' => ['nullable', 'string', 'max:50'],
            'attendees.*.is_officer' => ['required_with:attendees', 'boolean'],
            'attendees.*.is_signatory' => ['required_with:attendees', 'boolean'],
            'attendees.*.signature' => $this->signatureRules(),
        ]);
        if (array_key_exists('hasil_pendampingan', $data)) {
            $data['hasil_pendampingan'] = $this->richText->sanitize($data['hasil_pendampingan']);
        }

        return $data;
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

    private function persist(
        Client $client,
        ?BeritaAcara $record,
        array $data,
        Request $request,
        SignatureService $signatures,
    ): BeritaAcara {
        return DB::transaction(function () use ($client, $record, $data, $request, $signatures): BeritaAcara {
            $attendees = Arr::pull($data, 'attendees', []);
            $applicantSignature = Arr::pull($data, 'applicant_signature');
            unset($data['version'], $data['map_attachment'], $data['documentation_attachments'], $data['other_attachments']);

            if ($applicantSignature) {
                $data['tanda_tangan_pemohon'] = $signatures->store($applicantSignature);
            }
            if ($request->hasFile('map_attachment')) {
                $data['lampiran_peta'] = $request->file('map_attachment')->store('berita-acara/peta', 'local');
            }
            if ($request->hasFile('documentation_attachments')) {
                $data['lampiran_dokumentasi'] = $this->storeFiles(
                    $request->file('documentation_attachments', []),
                    'berita-acara/dokumentasi',
                );
            }
            if ($request->hasFile('other_attachments')) {
                $data['lampiran_lainnya'] = $this->storeFiles(
                    $request->file('other_attachments', []),
                    'berita-acara/lainnya',
                );
            }

            if ($record) {
                $record->update($data);
            } else {
                $record = $client->beritaAcara()->create($data);
            }

            foreach ($attendees as $attendeeData) {
                $attendeeId = Arr::pull($attendeeData, 'id');
                $signature = Arr::pull($attendeeData, 'signature');
                $payload = [
                    'nama' => $attendeeData['name'],
                    'jabatan' => $attendeeData['position'] ?? null,
                    'instansi' => $attendeeData['institution'] ?? null,
                    'email' => $attendeeData['email'] ?? null,
                    'no_hp' => $attendeeData['phone'] ?? null,
                    'is_officer' => $attendeeData['is_officer'],
                    'is_signatory' => $attendeeData['is_signatory'],
                ];
                if ($signature) {
                    $payload['tanda_tangan'] = $signatures->store($signature);
                }

                if ($attendeeId) {
                    $record->attendees()->whereKey($attendeeId)->firstOrFail()->update($payload);
                } else {
                    $record->attendees()->create($payload);
                }
            }

            $record->refresh();
            $record->update([
                'signing_deadline' => $record->calculateSigningDeadline()->endOfDay(),
            ]);

            return $record->fresh('attendees');
        });
    }

    private function storeFiles(array $files, string $directory): array
    {
        return collect($files)
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file): string => $file->store($directory, 'local'))
            ->values()
            ->all();
    }
}

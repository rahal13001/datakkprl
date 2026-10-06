<?php

namespace App\Services;

use App\Domain\Kkprl\ProposalAiRequestFailed;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KkprlAiService
{
    private string $apiKey;

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.key') ?? env('GEMINI_API_KEY', ''));
        $this->model = (string) config('services.gemini.model', 'gemini-3.8-flash');
    }

    /** @throws ProposalAiRequestFailed */
    public function chat(array $messages, string $systemPrompt): ?string
    {
        $contents = [];
        foreach ($messages as $message) {
            if (($message['role'] ?? null) === 'system_preview') {
                continue;
            }
            $contents[] = [
                'role' => $message['role'] === 'ai' ? 'model' : 'user',
                'parts' => [['text' => $message['content']]],
            ];
        }

        return $this->generate($contents, $systemPrompt, 0.4);
    }

    /** @throws ProposalAiRequestFailed */
    public function analyzeImage(string $base64Image, string $mimeType, string $prompt): ?string
    {
        return $this->generate([[
            'role' => 'user',
            'parts' => [
                ['text' => 'Ulas gambar sesuai bagian proposal dan informasi sumber yang diberikan.'],
                ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Image]],
            ],
        ]], $prompt, 0.2);
    }

    public function resetCooldown(): void
    {
        Cache::forget($this->cooldownKey());
    }

    private function generate(array $contents, string $systemPrompt, float $temperature): string
    {
        if ($this->apiKey === '') {
            $this->fail(new ProposalAiRequestFailed('configuration'));
        }

        // The same API project serves every chapter; switching from image to chat must respect its cooldown.
        $cooldown = Cache::get($this->cooldownKey());
        if (is_array($cooldown) && ($cooldown['until'] ?? 0) > now()->timestamp) {
            throw new ProposalAiRequestFailed($cooldown['reason'], 429, $cooldown['until'] - now()->timestamp);
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->apiKey])->connectTimeout(5)->timeout(30)
                ->post("{$this->baseUrl}/{$this->model}:generateContent", [
                    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                    'generationConfig' => ['temperature' => $temperature],
                    'contents' => $contents,
                ]);
        } catch (ConnectionException $exception) {
            $this->fail(new ProposalAiRequestFailed('connection'), ['exception' => $exception::class]);
        }

        if (! $response->successful()) {
            $failure = $this->responseFailure($response);
            if ($response->status() === 429) {
                $seconds = $failure->reason === 'quota_unavailable' ? 600 : ($failure->retryAfterSeconds ?? 60);
                Cache::put($this->cooldownKey(), ['reason' => $failure->reason, 'until' => now()->timestamp + $seconds], $seconds);
            }
            $this->fail($failure);
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->filter(fn ($part): bool => is_array($part) && ! ($part['thought'] ?? false) && is_string($part['text'] ?? null))
            ->pluck('text')->implode("\n");
        if (trim($text) === '') {
            $this->fail(new ProposalAiRequestFailed('empty_response', $response->status()));
        }

        return $text;
    }

    private function responseFailure(Response $response): ProposalAiRequestFailed
    {
        if ($response->status() !== 429) {
            return new ProposalAiRequestFailed(match ($response->status()) {
                401, 403 => 'access',
                404 => 'model',
                500, 502, 503, 504 => 'busy',
                default => 'request',
            }, $response->status());
        }

        $daily = false;
        $unavailable = false;
        $retry = null;
        foreach ($response->json('error.details', []) as $detail) {
            if (! is_array($detail)) {
                continue;
            }
            if (is_string($detail['retryDelay'] ?? null) && preg_match('/^(\d+(?:\.\d+)?)s$/', $detail['retryDelay'], $match)) {
                $retry = max(1, min(86400, (int) ceil((float) $match[1])));
            }
            foreach ($detail['violations'] ?? [] as $violation) {
                if (! is_array($violation)) {
                    continue;
                }
                $daily = $daily || preg_match('/perday|daily|\brpd\b/i', (string) ($violation['quotaId'] ?? '')) === 1;
                $unavailable = $unavailable || in_array($violation['quotaValue'] ?? null, ['0', 0], true);
            }
        }
        if ($unavailable) {
            return new ProposalAiRequestFailed('quota_unavailable', 429);
        }
        if ($retry === null && ctype_digit((string) $response->header('Retry-After'))) {
            $retry = max(1, min(86400, (int) $response->header('Retry-After')));
        }
        // Gemini daily quotas reset at midnight Pacific; use this only when the provider omits RetryInfo.
        $retry ??= $daily
            ? max(1, CarbonImmutable::now('America/Los_Angeles')->addDay()->startOfDay()->timestamp - now()->timestamp)
            : 60;

        return new ProposalAiRequestFailed($daily ? 'daily_quota' : 'rate_limit', 429, $retry);
    }

    private function cooldownKey(): string
    {
        return 'kkprl:gemini:cooldown:'.hash('sha256', $this->apiKey.'|'.$this->model);
    }

    private function fail(ProposalAiRequestFailed $failure, array $context = []): never
    {
        // Provider messages and request URLs can contain credentials or user data; log only our classification.
        Log::warning('KkprlAiService request failed.', [
            'model' => $this->model,
            'status' => $failure->httpStatus,
            'reason' => $failure->reason,
            'retry_after_seconds' => $failure->retryAfterSeconds,
        ] + $context);

        throw $failure;
    }
}

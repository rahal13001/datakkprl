<?php

namespace Tests\Feature;

use App\Domain\Kkprl\ProposalAiRequestFailed;
use App\Services\KkprlAiService;
use Carbon\CarbonImmutable;
use GuzzleHttp\Exception\ConnectTimeoutException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\KkprlApplicantFixture;
use Tests\TestCase;

class KkprlAiRateLimitTest extends TestCase
{
    use KkprlApplicantFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.gemini.key' => 'synthetic-test-key']);
        Cache::flush();
        Http::preventStrayRequests();
    }

    public static function quota(string $id, string $delay, string $limit = '20'): array
    {
        return ['error' => [
            'code' => 429,
            'status' => 'RESOURCE_EXHAUSTED',
            'message' => 'Untrusted provider text containing synthetic-test-key and PRIVATE_PROMPT.',
            'details' => [
                ['@type' => 'type.googleapis.com/google.rpc.QuotaFailure', 'violations' => [[
                    'quotaMetric' => 'generativelanguage.googleapis.com/generate_content_free_tier_requests',
                    'quotaId' => $id,
                    'quotaValue' => $limit,
                ]]],
                ['@type' => 'type.googleapis.com/google.rpc.RetryInfo', 'retryDelay' => $delay],
            ],
        ]];
    }

    #[Test]
    public function daily_quota_is_reported_and_repeated_image_or_chat_requests_do_not_hit_gemini(): void
    {
        $this->freezeTime();
        Http::fake(['*' => Http::response(self::quota('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '85236s'), 429)]);
        $service = app(KkprlAiService::class);
        foreach (['image', 'image', 'chat'] as $mode) {
            try {
                $mode === 'chat' ? $service->chat([['role' => 'user', 'content' => 'Halo']], 'Pendamping KKPRL')
                    : $service->analyzeImage('synthetic-image', 'image/png', 'Pendamping KKPRL');
                $this->fail('Quota exhaustion must be explained.');
            } catch (ProposalAiRequestFailed $failure) {
                $this->assertSame('daily_quota', $failure->reason);
                $this->assertSame(85236, $failure->retryAfterSeconds);
                $this->assertStringContainsString('Kuota harian', $failure->userMessage());
                $this->assertStringContainsString('23 jam', $failure->userMessage());
            }
        }
        Http::assertSentCount(1);
    }

    #[Test]
    public function temporary_rate_limit_recovers_after_the_provider_wait_expires(): void
    {
        $this->freezeTime();
        Http::fake(['*' => Http::sequence()
            ->push(self::quota('GenerateRequestsPerMinutePerProjectPerModel-FreeTier', '61.2s'), 429)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Narasi tersedia.']]]]]])]);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
                $this->fail('Temporary rate limit should be reported.');
            } catch (ProposalAiRequestFailed $failure) {
                $this->assertSame('rate_limit', $failure->reason);
                $this->assertSame(62, $failure->retryAfterSeconds);
            }
        }
        Http::assertSentCount(1);
        $this->travel(63)->seconds();
        $this->assertSame('Narasi tersedia.', app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt'));
        Http::assertSentCount(2);
    }

    #[Test]
    public function zero_quota_is_explained_as_unavailable_instead_of_promising_a_daily_reset(): void
    {
        Http::fake(['*' => Http::response(self::quota('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '60s', '0'), 429)]);
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
            $this->fail('Zero quota requires configuration guidance.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame('quota_unavailable', $failure->reason);
            $this->assertStringContainsString('Pengelola', $failure->userMessage());
        }
    }

    #[Test]
    public function unavailable_service_can_recover_without_a_long_quota_cooldown(): void
    {
        Http::fake(['*' => Http::sequence()->push(['error' => ['status' => 'UNAVAILABLE']], 503)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Hasil tersedia.']]]]]])]);
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
            $this->fail('Service outage should be explained.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame('busy', $failure->reason);
        }
        $this->assertSame('Hasil tersedia.', app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt'));
        Http::assertSentCount(2);
    }

    #[Test]
    public function logs_and_user_messages_do_not_expose_credentials_or_provider_content(): void
    {
        Log::spy();
        Http::fake(['*' => Http::response(self::quota('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '100s'), 429)]);
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'PRIVATE_PROMPT');
            $this->fail('Quota should be explained.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertStringNotContainsString('synthetic-test-key', $failure->userMessage());
            $this->assertStringNotContainsString('PRIVATE_PROMPT', $failure->userMessage());
        }
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => ! str_contains(json_encode([$message, $context]), 'synthetic-test-key')
            && ! str_contains(json_encode([$message, $context]), 'PRIVATE_PROMPT')
            && $context['reason'] === 'daily_quota' && $context['status'] === 429)->once();
    }

    #[Test]
    public function missing_credentials_and_connection_errors_are_identified(): void
    {
        config(['services.gemini.key' => '']);
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
            $this->fail('Configuration failure should be explained.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame('configuration', $failure->reason);
        }
        Http::assertNothingSent();
        config(['services.gemini.key' => 'synthetic-test-key']);
        Http::fake(fn () => throw new ConnectionException('PRIVATE_PROMPT synthetic-test-key'));
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
            $this->fail('Network failure should be explained.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame('connection', $failure->reason);
        }
    }

    #[Test]
    public function gemini_has_a_longer_configurable_response_deadline_without_automatic_paid_retries(): void
    {
        $options = [];
        Http::fake(function ($request, $requestOptions) use (&$options) {
            $options[] = $requestOptions;

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Narasi pasut.']]]]]]);
        });
        $this->assertSame('Narasi pasut.', app(KkprlAiService::class)->chat([['role' => 'user', 'content' => 'Ulas pasut']], 'prompt'));
        $this->assertSame(60, $options[0]['timeout']);
        $this->assertSame(15, $options[0]['connect_timeout']);
        config(['services.gemini.timeout' => 90, 'services.gemini.connect_timeout' => 10]);
        app(KkprlAiService::class)->chat([['role' => 'user', 'content' => 'Ulas pasut']], 'prompt');
        $this->assertSame(90, $options[1]['timeout']);
        $this->assertSame(10, $options[1]['connect_timeout']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function curl_timeout_is_explained_and_logs_only_safe_transport_diagnostics(): void
    {
        Log::spy();
        $transport = new ConnectTimeoutException('cURL error 28: PRIVATE_PROMPT synthetic-test-key', new Request('POST', 'https://example.test'));
        $attempts = 0;
        Http::fake(function () use ($transport, &$attempts) {
            $attempts++;

            throw new ConnectionException('cURL error 28: PRIVATE_PROMPT synthetic-test-key', 0, $transport);
        });
        try {
            app(KkprlAiService::class)->chat([['role' => 'user', 'content' => 'PRIVATE_PROMPT']], 'prompt');
            $this->fail('Timeout should be distinguished from other network failures.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame('timeout', $failure->reason);
            $this->assertStringContainsString('waktu tunggu', $failure->userMessage());
        }
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => $context['reason'] === 'timeout'
            && $context['curl_errno'] === 28 && is_int($context['elapsed_ms'])
            && ! str_contains(json_encode([$message, $context]), 'synthetic-test-key')
            && ! str_contains(json_encode([$message, $context]), 'PRIVATE_PROMPT'))->once();
        $this->assertSame(1, $attempts);
    }

    public static function transportFailures(): array
    {
        return [[6, 'dns'], [7, 'connection'], [60, 'tls'], [77, 'tls']];
    }

    #[Test]
    public function low_thinking_is_opt_in_for_supported_flash_and_does_not_change_normal_chat(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Narasi pasut.']]]]]])]);
        config(['services.gemini.model' => 'gemini-3.8-flash']);
        app(KkprlAiService::class)->chat([['role' => 'user', 'content' => 'Ulas pasut']], 'prompt', true);
        Http::assertSent(fn ($request) => ($request['generationConfig']['thinkingConfig']['thinkingLevel'] ?? null) === 'LOW');
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Narasi pasut.']]]]]])]);
        app(KkprlAiService::class)->chat([['role' => 'user', 'content' => 'Percakapan biasa']], 'prompt');
        Http::assertSent(fn ($request) => ! isset($request['generationConfig']['thinkingConfig']));
        config(['services.gemini.model' => 'gemini-2.5-flash']);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Narasi pasut.']]]]]])]);
        app(KkprlAiService::class)->chat([['role' => 'user', 'content' => 'Ulas pasut']], 'prompt', true);
        Http::assertSent(fn ($request) => ! isset($request['generationConfig']['thinkingConfig']));
    }

    #[Test]
    #[DataProvider('transportFailures')]
    public function network_failures_are_classified_without_showing_transport_details(int $errno, string $reason): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error '.$errno.': PRIVATE_PROMPT synthetic-test-key'));
        try {
            app(KkprlAiService::class)->chat([['role' => 'user', 'content' => 'Ulas pasut']], 'prompt');
            $this->fail('Transport error must be reported.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame($reason, $failure->reason);
            $this->assertStringNotContainsString('PRIVATE_PROMPT', $failure->userMessage());
            $this->assertStringNotContainsString('synthetic-test-key', $failure->userMessage());
        }
    }

    #[Test]
    public function empty_model_response_is_distinguished_from_an_api_quota_failure(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'SAFETY']]])]);
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
            $this->fail('Empty output should be explained.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame('empty_response', $failure->reason);
        }
    }

    #[Test]
    public function daily_quota_without_retry_info_uses_the_next_pacific_midnight(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00:00', 'UTC'));
        $body = self::quota('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '10s');
        array_pop($body['error']['details']);
        Http::fake(['*' => Http::response($body, 429)]);
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
            $this->fail('Daily quota should carry a reset interval.');
        } catch (ProposalAiRequestFailed $failure) {
            $this->assertSame('daily_quota', $failure->reason);
            $this->assertSame(23 * 3600, $failure->retryAfterSeconds);
        }
    }

    #[Test]
    public function operator_can_reset_only_gemini_cooldown_after_the_project_quota_changes(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(self::quota('GenerateRequestsPerDayPerProjectPerModel-FreeTier', '85236s'), 429)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Kuota tersedia kembali.']]]]]])]);
        try {
            app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt');
        } catch (ProposalAiRequestFailed) {
        }
        Cache::put('unrelated-application-data', 'keep', 600);
        $this->artisan('kkprl:ai-reset-cooldown')->assertSuccessful();
        $this->assertSame('keep', Cache::get('unrelated-application-data'));
        $this->assertSame('Kuota tersedia kembali.', app(KkprlAiService::class)->analyzeImage('image', 'image/png', 'prompt'));
        Http::assertSentCount(2);
    }
}

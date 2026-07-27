<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseCloudMessaging
{
    public function enabled(): bool
    {
        return filled(config('services.firebase.project_id'))
            && filled(config('services.firebase.credentials'));
    }

    public function send(string $registration, array $payload): array
    {
        if (! $this->enabled()) {
            return ['status' => 'skipped', 'message_id' => null, 'error_code' => 'firebase_not_configured'];
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post(
                'https://fcm.googleapis.com/v1/projects/'.config('services.firebase.project_id').'/messages:send',
                ['message' => [
                    'token' => $registration,
                    'notification' => [
                        'title' => $payload['title'],
                        'body' => $payload['body'],
                    ],
                    'data' => collect($payload)
                        ->except(['title', 'body'])
                        ->map(fn ($value): string => is_scalar($value) || $value === null
                            ? (string) ($value ?? '')
                            : json_encode($value))
                        ->all(),
                    'android' => [
                        'priority' => in_array($payload['event_type'] ?? '', [
                            'assignment.created',
                            'assignment.updated',
                            'schedule.updated',
                        ], true) ? 'HIGH' : 'NORMAL',
                        'notification' => [
                            'channel_id' => $this->channel($payload['event_type'] ?? ''),
                        ],
                    ],
                ]],
            );

        return $this->result($response);
    }

    private function accessToken(): string
    {
        return Cache::remember('firebase.messaging.access_token', now()->addMinutes(50), function (): string {
            $credentialsPath = (string) config('services.firebase.credentials');
            if (! str_starts_with($credentialsPath, DIRECTORY_SEPARATOR)
                && ! preg_match('/^[A-Za-z]:\\\\/', $credentialsPath)) {
                $credentialsPath = base_path($credentialsPath);
            }
            if (! is_file($credentialsPath)) {
                throw new RuntimeException('Firebase credentials file is unavailable.');
            }

            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $credentialsPath,
            );
            $token = $credentials->fetchAuthToken();

            if (blank($token['access_token'] ?? null)) {
                throw new RuntimeException('Unable to obtain a Firebase access token.');
            }

            return $token['access_token'];
        });
    }

    private function result(Response $response): array
    {
        if ($response->successful()) {
            return [
                'status' => 'sent',
                'message_id' => $response->json('name'),
                'error_code' => null,
            ];
        }

        return [
            'status' => 'failed',
            'message_id' => null,
            'error_code' => $response->json('error.details.0.errorCode')
                ?? $response->json('error.status')
                ?? 'fcm_http_'.$response->status(),
        ];
    }

    private function channel(string $eventType): string
    {
        return match (true) {
            str_starts_with($eventType, 'assignment.') => 'assignments',
            str_starts_with($eventType, 'schedule.') => 'schedule_changes',
            str_starts_with($eventType, 'request.') => 'new_requests',
            str_contains($eventType, 'feedback'), str_starts_with($eventType, 'satisfaction.') => 'feedback',
            default => 'system_updates',
        };
    }
}

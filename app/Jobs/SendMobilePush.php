<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendMobilePush implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public readonly int $userId,
        public readonly string $notificationId,
    ) {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(FirebaseCloudMessaging $fcm): void
    {
        $user = User::find($this->userId);
        $notification = $user?->notifications()->whereKey($this->notificationId)->first();
        if (! $user || ! $notification) {
            return;
        }

        $payload = array_merge($notification->data, ['notification_id' => $notification->id]);
        $devices = $user->devices()
            ->whereNull('disabled_at')
            ->where('notification_permission', 'granted')
            ->whereNotNull('push_registration_encrypted')
            ->get();

        $transientFailure = null;

        foreach ($devices as $device) {
            $alreadySent = DB::table('push_delivery_logs')
                ->where('notification_id', $notification->id)
                ->where('user_device_id', $device->id)
                ->where('status', 'sent')
                ->exists();
            if ($alreadySent) {
                continue;
            }

            $result = $fcm->send($device->push_registration, $payload);

            DB::table('push_delivery_logs')->updateOrInsert(
                [
                    'notification_id' => $notification->id,
                    'user_device_id' => $device->id,
                ],
                [
                    'event_type' => $payload['event_type'] ?? 'unknown',
                    'provider_message_id' => $result['message_id'],
                    'status' => $result['status'],
                    'error_code' => $result['error_code'],
                    'attempted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $invalidRegistration = in_array($result['error_code'], [
                'UNREGISTERED',
                'messaging/registration-token-not-registered',
            ], true);

            if ($invalidRegistration) {
                $device->update([
                    'disabled_at' => now(),
                    'push_registration' => null,
                ]);
            }

            if ($result['status'] === 'failed' && ! $invalidRegistration) {
                $transientFailure = $result['error_code'];
            }
        }

        if ($transientFailure) {
            throw new \RuntimeException("FCM delivery failed: {$transientFailure}");
        }
    }
}

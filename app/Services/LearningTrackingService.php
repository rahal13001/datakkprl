<?php

namespace App\Services;

use App\Models\LearningGroup;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAccess;
use App\Models\LearningMaterialOpen;
use App\Models\LearningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LearningTrackingService
{
    public function publicGroup(int $id, string $key): LearningGroup
    {
        $group = LearningGroup::query()->publiclyVisible()->findOrFail($id);

        throw_unless(hash_equals($group->accessKey(), $key), ValidationException::withMessages([
            'group_key' => 'Identitas grup pembelajaran tidak valid.',
        ]));

        return $group;
    }

    public function access(string $uuid, string $key): LearningMaterialAccess
    {
        $access = LearningMaterialAccess::query()->where('access_uuid', $uuid)->first();

        if (! $access || blank($access->group_key) || ! hash_equals($access->group_key, $key)) {
            throw ValidationException::withMessages([
                'access_uuid' => 'Akses grup pembelajaran tidak valid atau sudah tidak tersedia.',
            ]);
        }

        return $access;
    }

    public function createAccess(array $data, Request $request): array
    {
        $group = $this->publicGroup((int) $data['learning_group_id'], $data['group_key']);

        return DB::transaction(function () use ($data, $group, $request): array {
            $now = now();
            $access = LearningMaterialAccess::create([
                'access_uuid' => (string) Str::uuid(),
                'browser_uuid' => $data['browser_uuid'] ?? null,
                'learning_group_id' => $group->getKey(),
                'group_key' => $group->accessKey(),
                'group_title' => $group->title,
                'material_type' => null,
                'material_id' => null,
                'material_key' => $group->accessKey(),
                'material_title' => null,
                'name' => $data['name'],
                'institution' => $data['institution'],
                'access_purpose' => $data['access_purpose'],
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'visit_count' => 1,
            ]);

            $session = config('learning.detailed_tracking_enabled')
                ? $this->newSession($access, $request)
                : null;

            return [$access, $session];
        });
    }

    public function materialForAccess(LearningMaterialAccess $access, int $materialId): LearningMaterial
    {
        return LearningMaterial::query()
            ->publiclyVisible()
            ->where('learning_group_id', $access->learning_group_id)
            ->findOrFail($materialId);
    }

    public function recordMaterialOpen(LearningMaterialAccess $access, LearningMaterial $material): LearningMaterialOpen
    {
        return DB::transaction(function () use ($access, $material): LearningMaterialOpen {
            $openedAt = now();
            $open = LearningMaterialOpen::query()->firstOrCreate(
                [
                    'learning_material_access_id' => $access->getKey(),
                    'material_id' => $material->getKey(),
                ],
                [
                    'material_type' => $material->type,
                    'material_key' => $material->accessKey(),
                    'material_title' => $material->title,
                    'first_opened_at' => $openedAt,
                    'last_opened_at' => $openedAt,
                    'open_count' => 1,
                ],
            );

            if (! $open->wasRecentlyCreated) {
                $open->update([
                    'material_type' => $material->type,
                    'material_key' => $material->accessKey(),
                    'material_title' => $material->title,
                    'last_opened_at' => $openedAt,
                ]);
                $open->increment('open_count');
            }

            return $open->refresh();
        });
    }

    public function recordAccessVisit(LearningMaterialAccess $access): void
    {
        DB::transaction(function () use ($access): void {
            $access->update(['last_seen_at' => now()]);
            $access->increment('visit_count');
        });
    }

    public function startSession(LearningMaterialAccess $access, Request $request): LearningSession
    {
        return DB::transaction(function () use ($access, $request): LearningSession {
            $this->recordAccessVisit($access);

            return $this->newSession($access, $request);
        });
    }

    private function newSession(LearningMaterialAccess $access, Request $request): LearningSession
    {
        return $access->sessions()->create([
            'started_at' => now(),
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('data-protection.hash_key')) : null,
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);
    }
}

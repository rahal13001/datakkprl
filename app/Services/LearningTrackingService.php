<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\LearningMaterialAccess;
use App\Models\LearningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LearningTrackingService
{
    public function publicMaterial(int $id, string $key): LearningMaterial
    {
        $material = LearningMaterial::query()->publiclyVisible()->findOrFail($id);

        throw_unless(hash_equals($material->accessKey(), $key), ValidationException::withMessages([
            'material_key' => 'Identitas materi tidak valid.',
        ]));

        return $material;
    }

    public function access(string $uuid, string $key): LearningMaterialAccess
    {
        $access = LearningMaterialAccess::query()->where('access_uuid', $uuid)->first();

        if (! $access || ! hash_equals($access->material_key, $key)) {
            throw ValidationException::withMessages([
                'access_uuid' => 'Akses materi tidak valid atau sudah tidak tersedia.',
            ]);
        }

        return $access;
    }

    public function createAccess(array $data, Request $request): array
    {
        $material = $this->publicMaterial((int) $data['material_id'], $data['material_key']);

        return DB::transaction(function () use ($data, $material, $request): array {
            $now = now();
            $access = LearningMaterialAccess::create([
                'access_uuid' => (string) Str::uuid(),
                'browser_uuid' => $data['browser_uuid'] ?? null,
                'material_type' => $material->type,
                'material_id' => $material->getKey(),
                'material_key' => $material->accessKey(),
                'material_title' => $material->title,
                'name' => $data['name'],
                'institution' => $data['institution'],
                'access_purpose' => $data['access_purpose'],
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'visit_count' => 1,
            ]);

            $session = $this->newSession($access, $request);

            return [$access, $session];
        });
    }

    public function startSession(LearningMaterialAccess $access, Request $request): LearningSession
    {
        return DB::transaction(function () use ($access, $request): LearningSession {
            $access->update(['last_seen_at' => now()]);
            $access->increment('visit_count');

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

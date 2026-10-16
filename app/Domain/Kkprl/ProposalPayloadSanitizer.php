<?php

namespace App\Domain\Kkprl;

use Illuminate\Validation\ValidationException;

final class ProposalPayloadSanitizer
{
    /** @var list<string> */
    private const CONDITIONS = ['includes_reclamation', 'land_relation', 'has_existing_permits'];

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    public function sanitize(array $payload): array
    {
        $source = $payload['bag-1']['map_source'] ?? $payload['map_source'] ?? '';
        if (! is_string($source) || mb_strlen($source) > 300) {
            throw ValidationException::withMessages(['location_map' => 'Sumber data lokasi harus berupa teks maksimal 300 karakter.']);
        }
        $sanitized = [];

        foreach (self::CONDITIONS as $condition) {
            if (array_key_exists($condition, $payload)) {
                $sanitized[$condition] = $payload[$condition];
            }
        }

        $hasNestedChapter = false;
        foreach (['bag-1', 'bag-2', 'bag-3', 'bag-4', 'bag-5'] as $chapter) {
            if (! is_array($payload[$chapter] ?? null)) {
                continue;
            }

            $hasNestedChapter = true;
            $sanitized[$chapter] = $this->sanitizeChapter($chapter, $payload[$chapter]);
        }

        if (! $hasNestedChapter) {
            foreach ($this->allFields() as $field) {
                if (array_key_exists($field, $payload)) {
                    $sanitized[$field] = $payload[$field];
                }
            }
        }

        return $sanitized;
    }

    /** @param array<string, mixed> $chapterPayload @return array<string, mixed> */
    private function sanitizeChapter(string $chapter, array $chapterPayload): array
    {
        $fields = match ($chapter) {
            'bag-5' => array_merge(
                ProposalFieldCatalog::fields('bag-5-land'),
                ProposalFieldCatalog::fields('bag-5-permits'),
            ),
            default => ProposalFieldCatalog::fields($chapter),
        };
        $fields = array_values(array_diff($fields, self::CONDITIONS));

        // Preserve hidden UI states that shouldn't render in documents
        if ($chapter === 'bag-1') {
            $fields[] = 'coordinates_raw';
            $fields[] = 'map_source';
        }

        $sanitized = array_intersect_key($chapterPayload, array_flip($fields));

        if (array_key_exists('reclamation_schedule_rows', $sanitized) && is_array($sanitized['reclamation_schedule_rows'])) {
            $sanitized['reclamation_schedule_rows'] = array_map(
                fn (mixed $row): array => is_array($row)
                    ? array_intersect_key($row, array_flip(['activity', 'start_date', 'end_date', 'notes']))
                    : [],
                $sanitized['reclamation_schedule_rows'],
            );
        }

        return $sanitized;
    }

    /** @return list<string> */
    private function allFields(): array
    {
        return array_values(array_unique(array_merge(
            ProposalFieldCatalog::fields('bag-1'),
            ['coordinates_raw', 'map_source'],
            ProposalFieldCatalog::fields('bag-2'),
            ProposalFieldCatalog::fields('bag-3'),
            ProposalFieldCatalog::fields('bag-4'),
            ProposalFieldCatalog::fields('bag-5-land'),
            ProposalFieldCatalog::fields('bag-5-permits'),
        )));
    }
}

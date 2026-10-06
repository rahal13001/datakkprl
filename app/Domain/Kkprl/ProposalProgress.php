<?php

namespace App\Domain\Kkprl;

final class ProposalProgress
{
    public function __construct(
        private readonly ProposalChapterRules $chapterRules,
    ) {}

    /** @param array<string, mixed> $payload */
    public function evaluate(array $payload): ProposalProgressResult
    {
        $relevant = $this->chapterRules->relevantChapters($payload);
        $chapters = [];
        $errors = [];
        $totalFields = 0;
        $completedFields = 0;

        foreach ($relevant as $chapter) {
            $fields = $this->fieldsForChapter($chapter, $payload);
            $completed = count(array_filter(
                $fields,
                fn (string $field): bool => $this->filled($this->valueFor($payload, $field)),
            ));
            $total = count($fields);
            $issues = $this->issuesForChapter($chapter, $payload);
            $status = $issues !== [] ? 'error' : ($completed === $total ? 'complete' : 'incomplete');

            $chapters[$chapter] = compact('status', 'completed', 'total') + ['errors' => $issues];
            $errors[$chapter] = $issues;
            $totalFields += $total;
            $completedFields += $completed;
        }

        return new ProposalProgressResult(
            $chapters,
            $relevant,
            $totalFields === 0 ? 0 : (int) floor(($completedFields / $totalFields) * 100),
            $errors,
        );
    }

    /** @return list<string> */
    public static function requiredFields(string $chapter): array
    {
        return ProposalFieldCatalog::fields($chapter);
    }

    /** @return list<string> */
    private function fieldsForChapter(string $chapter, array $payload): array
    {
        if ($chapter !== 'bag-5') {
            return self::requiredFields($chapter);
        }

        $fields = [];

        if (($payload['land_relation'] ?? null) === 'adjacent') {
            $fields = array_merge($fields, self::requiredFields('bag-5-land'));
        }

        if ($this->truthy($payload['has_existing_permits'] ?? false)) {
            $fields = array_merge($fields, self::requiredFields('bag-5-permits'));
        }

        return $fields;
    }

    private function filled(mixed $value): bool
    {
        if (is_array($value)) {
            if ($value === []) {
                return false;
            }

            if (array_is_list($value) && is_array($value[0] ?? null) && ! array_is_list($value[0])) {
                return collect($value)->every(function (mixed $row): bool {
                    if (! is_array($row)) {
                        return false;
                    }

                    foreach (['activity', 'start_date', 'end_date', 'notes'] as $field) {
                        if (! $this->filled($row[$field] ?? null)) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            return true;
        }

        return is_string($value)
            ? trim($value) !== ''
            : $value !== null;
    }

    private function valueFor(array $payload, string $field): mixed
    {
        if (array_key_exists($field, $payload)) {
            return $payload[$field];
        }

        foreach (['bag-1', 'bag-2', 'bag-3', 'bag-4', 'bag-5'] as $chapter) {
            if (isset($payload[$chapter]) && is_array($payload[$chapter]) && array_key_exists($field, $payload[$chapter])) {
                return $payload[$chapter][$field];
            }
        }

        return null;
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true'], true);
    }

    /** @return list<string> */
    private function issuesForChapter(string $chapter, array $payload): array
    {
        if ($chapter !== 'bag-1') {
            return [];
        }

        $issues = [];
        $shape = $this->valueFor($payload, 'shape_type');
        if ($this->filled($shape) && ! in_array($shape, ['point', 'line', 'polygon'], true)) {
            $issues[] = 'fields.shape_type';
        }

        $text = $this->valueFor($payload, 'coordinates');
        $raw = $this->valueFor($payload, 'coordinates_raw');
        if ($raw === null && (! $this->filled($text) || (is_string($text) && mb_strtolower(trim($text)) === 'tidak berlaku'))) {
            return $issues;
        }

        $points = $raw === null ? $this->pointsFromText($text) : (is_string($raw) ? json_decode($raw, true) : null);
        $minimum = match ($shape) {
            'line' => 2,
            'polygon' => 3,
            default => 1,
        };

        if (! is_array($points) || ! array_is_list($points) || count($points) < $minimum) {
            $issues[] = 'fields.coordinates';

            return $issues;
        }

        foreach ($points as $point) {
            foreach (['lat_dd' => [-90, 90], 'lng_dd' => [-180, 180]] as $axis => [$min, $max]) {
                $value = is_array($point) ? ($point[$axis] ?? null) : null;
                if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < $min || (float) $value > $max) {
                    $issues[] = 'fields.coordinates';

                    return $issues;
                }
            }
        }

        return $issues;
    }

    /** @return list<array{lat_dd: string, lng_dd: string}>|null */
    private function pointsFromText(mixed $text): ?array
    {
        if (! is_string($text)) {
            return null;
        }

        $points = [];
        foreach (preg_split('/\R/', trim($text)) as $line) {
            if (! preg_match('/^Titik \d+: Latitude [^()]+\(([^()]+)\), Longitude [^()]+\(([^()]+)\)$/u', trim($line), $matches)) {
                return null;
            }
            $points[] = ['lat_dd' => trim($matches[1]), 'lng_dd' => trim($matches[2])];
        }

        return $points;
    }
}

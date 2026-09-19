<?php

namespace App\Domain\Kkprl;

final class ProposalChapterRules
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public function relevantChapters(array $payload): array
    {
        $chapters = ['bag-1', 'bag-2', 'bag-3'];

        if ($this->isRelevant('bag-4', $payload)) {
            $chapters[] = 'bag-4';
        }

        if ($this->isRelevant('bag-5', $payload)) {
            $chapters[] = 'bag-5';
        }

        return $chapters;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function isRelevant(string $chapter, array $payload): bool
    {
        return match ($chapter) {
            'bag-1', 'bag-2', 'bag-3' => true,
            'bag-4' => $this->truthy($payload['includes_reclamation'] ?? false),
            'bag-5' => ($payload['land_relation'] ?? null) === 'adjacent'
                || $this->truthy($payload['has_existing_permits'] ?? false),
            default => false,
        };
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true'], true);
    }
}

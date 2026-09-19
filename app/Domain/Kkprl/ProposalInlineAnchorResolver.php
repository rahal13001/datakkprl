<?php

namespace App\Domain\Kkprl;

final class ProposalInlineAnchorResolver
{
    /** @param array<string, mixed> $payload */
    public function resolve(string $chapter, string $anchor, array $payload): ?string
    {
        $needle = mb_strtolower(trim($anchor));
        if ($needle === '') {
            return null;
        }

        $definitions = ProposalFieldCatalog::definitionsForPayload($chapter, $payload);
        $values = $this->valuesForChapter($chapter, $payload, array_column($definitions, 'key'));
        $fieldAnchor = str_starts_with($needle, mb_strtolower($chapter).'.')
            ? substr($needle, strlen($chapter) + 1)
            : $needle;

        foreach ($definitions as $definition) {
            if (mb_strtolower($definition['key']) === $fieldAnchor || mb_strtolower($definition['label']) === $needle) {
                return $definition['key'];
            }
        }

        foreach ($values as $field => $value) {
            if (is_scalar($value) && str_contains(mb_strtolower((string) $value), $needle)) {
                return (string) $field;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $payload @param list<string> $fields @return array<string, mixed> */
    private function valuesForChapter(string $chapter, array $payload, array $fields): array
    {
        if (isset($payload[$chapter]) && is_array($payload[$chapter])) {
            return array_intersect_key($payload[$chapter], array_flip($fields));
        }

        return array_intersect_key($payload, array_flip($fields));
    }
}

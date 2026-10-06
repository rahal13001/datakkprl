<?php

namespace App\Domain\Kkprl;

final class ProposalAiResponseParser
{
    /** @return array{text: string, patch: ?array, hasDraft: bool} */
    public function parse(string $response): array
    {
        $patch = [];
        $hasDraft = false;
        $invalid = false;
        $extract = function (string $json) use (&$patch, &$hasDraft, &$invalid): void {
            $hasDraft = true;
            $decoded = json_decode(trim($json), true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded) || array_is_list($decoded)) {
                $invalid = true;

                return;
            }
            $patch = array_replace($patch, $decoded);
        };

        // An unfinished fence must never expose an unfinished payload in the chat.
        $text = preg_replace_callback('/```(?:json\b)?[ \t]*\R?(.*?)(?:```|\z)/is', function (array $matches) use ($extract): string {
            $extract($matches[1]);

            return '';
        }, $response);

        // Also accept unfenced objects, including nested rows and braces in strings.
        while (preg_match('/\{\s*"|(?m:^[ \t]*\K\[)/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[0][1];
            $end = $this->jsonEnd($text, $start);
            $extract(substr($text, $start, $end - $start));
            $text = substr($text, 0, $start).substr($text, $end);
        }

        return [
            'text' => trim($text),
            'patch' => $hasDraft && ! $invalid ? $patch : null,
            'hasDraft' => $hasDraft,
        ];
    }

    private function jsonEnd(string $text, int $start): int
    {
        $stack = [];
        $quoted = false;
        $escaped = false;
        for ($index = $start, $length = strlen($text); $index < $length; $index++) {
            $character = $text[$index];
            if ($quoted) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === '"') {
                    $quoted = false;
                }

                continue;
            }
            if ($character === '"') {
                $quoted = true;
            } elseif ($character === '{' || $character === '[') {
                $stack[] = $character;
            } elseif ($character === '}' || $character === ']') {
                $opening = array_pop($stack);
                if (($character === '}' && $opening !== '{') || ($character === ']' && $opening !== '[') || $stack === []) {
                    return $index + 1;
                }
            }
        }

        return strlen($text);
    }
}

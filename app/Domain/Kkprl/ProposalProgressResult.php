<?php

namespace App\Domain\Kkprl;

final readonly class ProposalProgressResult
{
    /**
     * @param  array<string, array{status: string, completed: int, total: int, errors: list<string>}>  $chapters
     * @param  array<string, list<string>>  $errors
     * @param  list<string>  $relevantChapters
     */
    public function __construct(
        public array $chapters,
        public array $relevantChapters,
        public int $percent,
        public array $errors = [],
    ) {}
}

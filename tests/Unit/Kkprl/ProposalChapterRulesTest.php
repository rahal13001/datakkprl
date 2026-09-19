<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalChapterRules;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProposalChapterRulesTest extends TestCase
{
    #[Test]
    public function base_chapters_are_always_relevant(): void
    {
        $rules = new ProposalChapterRules;

        $this->assertSame(['bag-1', 'bag-2', 'bag-3'], $rules->relevantChapters([]));
    }

    #[Test]
    public function reclamation_adds_bag_four(): void
    {
        $rules = new ProposalChapterRules;

        $this->assertSame(
            ['bag-1', 'bag-2', 'bag-3', 'bag-4'],
            $rules->relevantChapters(['includes_reclamation' => true]),
        );
    }

    #[Test]
    public function bag_five_is_relevant_only_for_selected_subsections(): void
    {
        $rules = new ProposalChapterRules;

        $this->assertFalse($rules->isRelevant('bag-5', []));
        $this->assertTrue($rules->isRelevant('bag-5', ['land_relation' => 'adjacent']));
        $this->assertTrue($rules->isRelevant('bag-5', ['has_existing_permits' => true]));
    }

    #[Test]
    public function inactive_conditional_chapters_are_not_relevant(): void
    {
        $rules = new ProposalChapterRules;

        $this->assertFalse($rules->isRelevant('bag-4', ['includes_reclamation' => false]));
        $this->assertFalse($rules->isRelevant('bag-5', [
            'land_relation' => 'not_adjacent',
            'has_existing_permits' => false,
        ]));
    }

    #[Test]
    public function string_zero_from_a_select_does_not_activate_permits(): void
    {
        $this->assertFalse((new ProposalChapterRules)->isRelevant('bag-5', [
            'land_relation' => 'not_adjacent',
            'has_existing_permits' => '0',
        ]));
    }
}

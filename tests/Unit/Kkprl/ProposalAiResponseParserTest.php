<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalAiResponseParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProposalAiResponseParserTest extends TestCase
{
    #[Test]
    public function ordinary_follow_up_questions_are_preserved(): void
    {
        $this->assertSame(['text' => 'Apakah ada jalur pelayaran di sekitar lokasi?', 'patch' => null, 'hasDraft' => false],
            (new ProposalAiResponseParser)->parse('Apakah ada jalur pelayaran di sekitar lokasi?'));
    }

    #[Test]
    public function bare_nested_drafts_allow_braces_and_escaped_quotes_inside_strings(): void
    {
        $patch = ['reclamation_schedule_rows' => [[
            'activity' => 'Persiapan', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30',
            'notes' => 'Catatan {lokasi}, "tahap awal", dan \\.',
        ]]];
        $parsed = (new ProposalAiResponseParser)->parse('Saya memperbarui jadwal. '.json_encode($patch).' Silakan periksa kembali.');

        $this->assertSame($patch, $parsed['patch']);
        $this->assertSame('Saya memperbarui jadwal.  Silakan periksa kembali.', $parsed['text']);
    }

    #[Test]
    public function multiple_data_blocks_are_merged_and_hidden(): void
    {
        $parsed = (new ProposalAiResponseParser)->parse("```json\n{\"mangrove\":\"Ada.\"}\n```\n```JSON\n{\"seagrass\":\"Belum diketahui.\"}\n```");

        $this->assertSame(['mangrove' => 'Ada.', 'seagrass' => 'Belum diketahui.'], $parsed['patch']);
        $this->assertSame('', $parsed['text']);
    }

    #[Test]
    public function a_broken_block_rejects_the_entire_patch(): void
    {
        $parsed = (new ProposalAiResponseParser)->parse('```json {"mangrove":"Ada."} ``` ```json {"seagrass":"Terpotong');

        $this->assertNull($parsed['patch']);
        $this->assertTrue($parsed['hasDraft']);
        $this->assertSame('', $parsed['text']);
    }

    #[Test]
    public function bare_arrays_and_truncated_objects_are_hidden_without_becoming_drafts(): void
    {
        foreach (['[{"mangrove":"Ada."}]', '{"marine_space_use_narrative":"Terpotong'] as $response) {
            $this->assertSame(['text' => '', 'patch' => null, 'hasDraft' => true], (new ProposalAiResponseParser)->parse($response));
        }
    }
}

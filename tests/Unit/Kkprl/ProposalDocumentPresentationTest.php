<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalDocumentPresentation;
use PHPUnit\Framework\TestCase;

class ProposalDocumentPresentationTest extends TestCase
{
    public function test_editor_markup_becomes_paragraphs_without_remote_or_executable_content(): void
    {
        $blocks = ProposalDocumentPresentation::paragraphs('<p>Satu &amp; dua.<script>LEAK</script></p><p><strong>Penting</strong><br>Baris berikut.</p><ul><li>Butir pertama.</li><li>Butir kedua.</li></ul><img src="https://invalid.example/secret"><iframe>LEAK</iframe>');
        $plain = implode("\n", array_column($blocks, 'text'));
        $this->assertStringContainsString('Satu & dua.', $plain);
        $this->assertStringContainsString('Baris berikut.', $plain);
        $this->assertStringContainsString('Butir pertama.', $plain);
        $this->assertStringNotContainsString('LEAK', $plain);
        $this->assertStringNotContainsString('invalid.example', $plain);
        $this->assertGreaterThanOrEqual(4, count($blocks));
    }

    public function test_plain_text_zero_boolean_and_nested_data_do_not_become_blank_or_json(): void
    {
        $this->assertSame('0', ProposalDocumentPresentation::text(0));
        $this->assertSame('Tidak', ProposalDocumentPresentation::text(false));
        $this->assertSame('Ya', ProposalDocumentPresentation::text(true));
        $this->assertSame("Satu\n\nDua", ProposalDocumentPresentation::text("Satu\n\nDua"));
        $text = ProposalDocumentPresentation::text(['activity' => 'Pemasangan', 'notes' => ['Tahap satu', 'Tahap dua']]);
        $this->assertStringContainsString('Pemasangan', $text);
        $this->assertStringContainsString('Tahap dua', $text);
        $this->assertStringNotContainsString('{', $text);
    }
}

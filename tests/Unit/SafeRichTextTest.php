<?php

namespace Tests\Unit;

use App\Services\SafeRichText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SafeRichTextTest extends TestCase
{
    #[Test]
    public function it_keeps_basic_formatting_and_removes_executable_markup(): void
    {
        $clean = (new SafeRichText)->sanitize(
            '<p onclick="alert(1)">Safe <strong style="color:red">text</strong></p>'.
            '<img src=x onerror="alert(2)"><script>alert(3)</script><svg onload="alert(4)"/>',
        );

        $this->assertSame('<p>Safe <strong>text</strong></p>', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('svg', $clean);

        $this->assertSame(
            '<table><tr><td>Kolom</td></tr></table>',
            (new SafeRichText)->sanitize('<table onclick="x"><tr><td style="x">Kolom</td></tr></table>'),
        );
    }
}

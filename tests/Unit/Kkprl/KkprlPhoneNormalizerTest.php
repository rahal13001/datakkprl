<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\KkprlPhoneNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KkprlPhoneNormalizerTest extends TestCase
{
    #[Test]
    public function local_and_international_indonesian_formats_resolve_to_same_value(): void
    {
        $normalizer = new KkprlPhoneNormalizer;

        $this->assertSame('6281234567890', $normalizer->normalize('0812-3456-7890'));
        $this->assertSame('6281234567890', $normalizer->normalize('+62 812 3456 7890'));
        $this->assertSame('6281234567890', $normalizer->normalize('0062 812 3456 7890'));
    }

    #[Test]
    public function blank_phone_is_normalized_to_null(): void
    {
        $this->assertNull((new KkprlPhoneNormalizer)->normalize('  '));
    }
}

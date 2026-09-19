<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\AttachmentQuota;
use App\Domain\Kkprl\AttachmentQuotaViolation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachmentQuotaTest extends TestCase
{
    #[Test]
    public function exact_limits_are_allowed(): void
    {
        $quota = new AttachmentQuota;

        $quota->assertWithin(10, 15_728_640);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function more_than_ten_active_files_is_rejected_as_count_violation(): void
    {
        $this->expectException(AttachmentQuotaViolation::class);
        $this->expectExceptionMessage('attachment_count');

        (new AttachmentQuota)->assertWithin(11, 0);
    }

    #[Test]
    public function more_than_fifteen_mib_is_rejected_as_size_violation(): void
    {
        $this->expectException(AttachmentQuotaViolation::class);
        $this->expectExceptionMessage('attachment_size');

        (new AttachmentQuota)->assertWithin(1, 15_728_641);
    }
}

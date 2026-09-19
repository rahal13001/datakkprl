<?php

namespace App\Domain\Kkprl;

use InvalidArgumentException;

final class AttachmentQuota
{
    public const MAX_FILES_PER_CHAPTER = 10;

    public const MAX_BYTES_PER_CHAPTER = 15_728_640;

    public function assertWithin(int $activeFileCount, int $activeBytes): void
    {
        if ($activeFileCount < 0 || $activeBytes < 0) {
            throw new InvalidArgumentException('Attachment quota values cannot be negative.');
        }

        if ($activeFileCount > self::MAX_FILES_PER_CHAPTER) {
            throw new AttachmentQuotaViolation(
                'attachment_count',
                'Maximum 10 active attachments per chapter exceeded.',
            );
        }

        if ($activeBytes > self::MAX_BYTES_PER_CHAPTER) {
            throw new AttachmentQuotaViolation(
                'attachment_size',
                'Maximum 15 MiB per chapter exceeded.',
            );
        }
    }
}

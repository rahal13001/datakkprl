<?php

use App\Domain\Kkprl\ProposalProgress;
use App\Services\KkprlProposalAccessService;
use App\Services\KkprlProposalAttachmentService;
use App\Services\KkprlProposalDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

Storage::fake('kkprl_private');
DB::beginTransaction();

try {
    $proposal = app(KkprlProposalAccessService::class)->startDraft(['phone' => '0812-3456-7890']);
    $payload = [];

    foreach (['bag-1', 'bag-2', 'bag-3'] as $chapter) {
        foreach (ProposalProgress::requiredFields($chapter) as $field) {
            $payload[$field] = match ($field) {
                'latitude' => '-6.2',
                'longitude' => '106.8',
                'site_plan_description' => 'Uraian site plan synthetic untuk smoke viewer.',
                'schedule' => 'Januari sampai Maret 2026',
                default => 'Terisi untuk smoke viewer',
            };
        }
    }
    $proposal->update(['payload' => $payload]);

    app(KkprlProposalAttachmentService::class)->store(
        $proposal,
        'bag-1',
        UploadedFile::fake()->createWithContent('support.pdf', Pdf::loadHTML('<h1>PDF pendukung smoke</h1><p>Teks terbaca.</p>')->output()),
        ['placement' => 'appendix', 'caption' => 'PDF pendukung smoke'],
    );
    app(KkprlProposalAttachmentService::class)->store(
        $proposal,
        'bag-1',
        UploadedFile::fake()->image('site-plan.png', 1200, 650),
        ['placement' => 'inline', 'anchor_key' => 'Uraian site plan synthetic untuk smoke viewer.', 'caption' => 'Site plan portrait/landscape smoke'],
    );

    $documents = app(KkprlProposalDocumentService::class)->generateChapterFormats($proposal->fresh(), 'bag-1');
    foreach ($documents as $document) {
        $extension = $document->format === 'docx' ? 'docx' : 'pdf';
        $target = 'C:/Temp/kkprl-golden-smoke-bag-1.'.$extension;
        file_put_contents($target, Storage::disk('kkprl_private')->get($document->storage_path));
        echo $target.PHP_EOL;
    }
} finally {
    DB::rollBack();
}

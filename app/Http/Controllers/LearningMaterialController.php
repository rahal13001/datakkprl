<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use Illuminate\Support\Facades\Storage;

class LearningMaterialController extends Controller
{
    public function pdf(LearningMaterial $material)
    {
        $this->abortUnlessPublicPdf($material);

        LearningMaterial::whereKey($material->getKey())->increment('view_count');

        return response()->file(
            Storage::disk('public')->path($material->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$this->pdfFilename($material).'"',
            ]
        );
    }

    public function download(LearningMaterial $material)
    {
        $this->abortUnlessPublicPdf($material);

        LearningMaterial::whereKey($material->getKey())->increment('download_count');

        return response()->download(
            Storage::disk('public')->path($material->pdf_path),
            $this->pdfFilename($material),
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function abortUnlessPublicPdf(LearningMaterial $material): void
    {
        $material->loadMissing('group.category');

        abort_unless($material->isPdf(), 404);
        abort_unless($material->is_published, 404);
        abort_unless($material->group?->is_published, 404);
        abort_unless($material->group?->category?->is_active, 404);
        abort_unless(filled($material->pdf_path), 404);
        abort_unless(Storage::disk('public')->exists($material->pdf_path), 404);
    }

    protected function pdfFilename(LearningMaterial $material): string
    {
        $extension = pathinfo($material->pdf_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = (string) str($material->title)->slug();
        $filename = $filename ?: 'materi-belajar-kkprl';

        return "{$filename}.{$extension}";
    }
}

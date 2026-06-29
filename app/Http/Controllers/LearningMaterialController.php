<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Services\LearningTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningMaterialController extends Controller
{
    public function __construct(private readonly LearningTrackingService $tracking) {}

    public function show(LearningMaterial $material)
    {
        $this->abortUnlessPublic($material);

        return view('learning.material', compact('material'));
    }

    public function pdf(Request $request, LearningMaterial $material)
    {
        $this->abortUnlessPublicPdf($material);
        $this->authorizeAccess($request, $material);

        LearningMaterial::whereKey($material->getKey())->increment('view_count');

        return response()->file(
            Storage::disk('public')->path($material->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$this->pdfFilename($material).'"',
            ]
        );
    }

    public function download(Request $request, LearningMaterial $material)
    {
        $this->abortUnlessPublicPdf($material);
        $this->authorizeAccess($request, $material);

        LearningMaterial::whereKey($material->getKey())->increment('download_count');

        return response()->download(
            Storage::disk('public')->path($material->pdf_path),
            $this->pdfFilename($material),
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function abortUnlessPublicPdf(LearningMaterial $material): void
    {
        $this->abortUnlessPublic($material);

        abort_unless($material->isPdf(), 404);
        abort_unless(filled($material->pdf_path), 404);
        abort_unless(Storage::disk('public')->exists($material->pdf_path), 404);
    }

    protected function abortUnlessPublic(LearningMaterial $material): void
    {
        $material->loadMissing('group.category');
        abort_unless($material->is_published, 404);
        abort_unless($material->group?->is_published, 404);
        abort_unless($material->group?->category?->is_active, 404);
    }

    protected function authorizeAccess(Request $request, LearningMaterial $material): void
    {
        $uuid = (string) $request->query('access_uuid');
        abort_if($uuid === '', 403);

        try {
            $this->tracking->access($uuid, $material->accessKey());
        } catch (\Throwable) {
            abort(403);
        }
    }

    protected function pdfFilename(LearningMaterial $material): string
    {
        $extension = pathinfo($material->pdf_path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = (string) str($material->title)->slug();
        $filename = $filename ?: 'materi-belajar-kkprl';

        return "{$filename}.{$extension}";
    }
}

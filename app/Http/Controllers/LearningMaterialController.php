<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\LearningMaterialAccess;
use App\Services\LearningTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningMaterialController extends Controller
{
    public function __construct(private readonly LearningTrackingService $tracking) {}

    public function show(Request $request, LearningMaterial $material)
    {
        $this->abortUnlessPublic($material);
        $access = $this->resolveAccess($request, $material);

        if (! $access) {
            return redirect()->route('belajar-kkprl.group', $material->group);
        }

        $this->tracking->recordMaterialOpen($access, $material);

        if ($material->isVideo()) {
            LearningMaterial::whereKey($material->getKey())->increment('view_count');
        }

        return view('learning.material', compact('material', 'access'));
    }

    public function pdf(Request $request, LearningMaterial $material)
    {
        $this->abortUnlessPublicPdf($material);
        abort_unless($this->resolveAccess($request, $material), 403);

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
        abort_unless($this->resolveAccess($request, $material), 403);

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

    protected function resolveAccess(Request $request, LearningMaterial $material): ?LearningMaterialAccess
    {
        $uuid = (string) $request->query('access_uuid');
        if ($uuid === '') {
            return null;
        }

        try {
            return $this->tracking->access($uuid, $material->group->accessKey());
        } catch (\Throwable) {
            return null;
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

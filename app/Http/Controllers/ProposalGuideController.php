<?php

namespace App\Http\Controllers;

use App\Models\ProposalGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProposalGuideController extends Controller
{
    public function index()
    {
        $guides = ProposalGuide::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get();

        return view('guides.index', compact('guides'));
    }

    public function pdf(Request $request, ProposalGuide $guide)
    {
        $this->assertAvailable($guide);

        $response = response()->file(Storage::disk('public')->path($guide->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->filename($guide).'"',
            'Cache-Control' => 'no-store, private',
        ]);

        $this->recordAccess($request, $guide, 'view_count');

        return $response;
    }

    public function download(Request $request, ProposalGuide $guide)
    {
        $this->assertAvailable($guide);

        $response = response()->download(Storage::disk('public')->path($guide->pdf_path), $this->filename($guide), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, private',
        ]);

        $this->recordAccess($request, $guide, 'download_count');

        return $response;
    }

    private function recordAccess(Request $request, ProposalGuide $guide, string $counter): void
    {
        if (! $request->isMethod('GET') || $request->headers->has('Range')) {
            return;
        }

        $viewToken = $request->query('view_token');
        if ($counter === 'view_count' && is_string($viewToken) && Str::isUuid($viewToken)) {
            $key = 'proposal-guide-view:'.$guide->getKey().':'.hash('sha256', $viewToken);
            if (! Cache::add($key, true, now()->addMinutes(30))) {
                return;
            }
        }

        ProposalGuide::whereKey($guide->getKey())->increment($counter);
    }

    private function assertAvailable(ProposalGuide $guide): void
    {
        abort_unless($guide->is_published && filled($guide->pdf_path) && Storage::disk('public')->exists($guide->pdf_path), 404);
    }

    private function filename(ProposalGuide $guide): string
    {
        return (str($guide->title)->slug()->toString() ?: 'pedoman-kkprl').'.pdf';
    }
}

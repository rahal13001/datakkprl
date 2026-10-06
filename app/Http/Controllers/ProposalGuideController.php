<?php

namespace App\Http\Controllers;

use App\Models\ProposalGuide;
use Illuminate\Support\Facades\Storage;

class ProposalGuideController extends Controller
{
    public function index()
    {
        $guides = ProposalGuide::query()->where('is_published', true)->orderBy('sort_order')->orderBy('id')->get();

        return view('guides.index', compact('guides'));
    }

    public function pdf(ProposalGuide $guide)
    {
        $this->assertAvailable($guide);

        return response()->file(Storage::disk('public')->path($guide->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->filename($guide).'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function download(ProposalGuide $guide)
    {
        $this->assertAvailable($guide);

        return response()->download(Storage::disk('public')->path($guide->pdf_path), $this->filename($guide), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, private',
        ]);
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

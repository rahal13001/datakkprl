<?php

namespace App\Livewire\Concerns;

use App\Models\KkprlProposal;
use App\Services\KkprlApplicantAuthService;
use App\Services\KkprlProposalAccessService;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\WithFileUploads;

trait WithKkprlFileUploads
{
    use WithFileUploads;

    #[Renderless]
    public function _startUpload($name, $fileInfo, $isMultiple)
    {
        $proposal = KkprlProposal::findOrFail($this->proposalId);
        app(KkprlProposalAccessService::class)->assertCanAccess($proposal);
        abort_if($proposal->isLocked(), 403);
        $account = app(KkprlApplicantAuthService::class)->current();
        $url = URL::temporarySignedRoute('kkprl.applicant.upload', now()->addMinutes(FileUploadConfiguration::maxUploadTime()), [
            'proposal' => $proposal->id,
            'applicant' => $account->id,
            'session_version' => $account->session_version,
        ]);
        $this->dispatch('upload:generatedSignedUrl', name: $name, url: $url)->self();
    }
}

<?php

namespace App\Http\Controllers;

use App\Domain\Kkprl\ProposalAccessDenied;
use App\Models\KkprlProposal;
use App\Services\KkprlApplicantAuthService;
use App\Services\KkprlProposalAccessService;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\FileUploadController;

final class KkprlApplicantUploadController extends FileUploadController
{
    public function handle()
    {
        abort_unless(request()->hasValidSignature(), 401);

        return DB::transaction(function () {
            $proposal = KkprlProposal::lockForUpdate()->findOrFail(request('proposal'));
            try {
                app(KkprlProposalAccessService::class)->assertCanAccess($proposal);
            } catch (ProposalAccessDenied) {
                abort(403);
            }
            $account = app(KkprlApplicantAuthService::class)->current(lock: true);
            abort_unless($account && $account->id === (int) request('applicant')
                && $account->session_version === (int) request('session_version') && ! $proposal->isLocked(), 403);

            return parent::handle();
        });
    }
}

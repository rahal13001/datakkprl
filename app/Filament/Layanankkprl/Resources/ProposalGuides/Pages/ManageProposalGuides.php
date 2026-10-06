<?php

namespace App\Filament\Layanankkprl\Resources\ProposalGuides\Pages;

use App\Filament\Layanankkprl\Resources\ProposalGuides\ProposalGuideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProposalGuides extends ManageRecords
{
    protected static string $resource = ProposalGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Pedoman')];
    }
}

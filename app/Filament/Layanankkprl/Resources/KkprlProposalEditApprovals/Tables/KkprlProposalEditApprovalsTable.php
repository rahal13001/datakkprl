<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposalEditApprovals\Tables;

use App\Models\KkprlProposalEditApproval;
use App\Services\KkprlProposalEditApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class KkprlProposalEditApprovalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('proposal.ticket_number')->label('Nomor tiket')->searchable()->copyable(),
                TextColumn::make('revision.revision_label')->label('Revisi'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('requested_by')->label('Petugas A'),
                TextColumn::make('approved_by')->label('Petugas B')->placeholder('Belum'),
                TextColumn::make('expires_at')->label('Kedaluwarsa')->dateTime(),
                TextColumn::make('requested_at')->label('Diminta')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->visible(fn (KkprlProposalEditApproval $record): bool => $record->status === 'pending' && auth()->user()?->can('ApproveEdit:KkprlProposal') === true)
                    ->form([
                        DateTimePicker::make('expires_at')->label('Berlaku sampai')->required()->minDate(now()->addMinute()),
                    ])
                    ->action(fn (KkprlProposalEditApproval $record, array $data): KkprlProposalEditApproval => app(KkprlProposalEditApprovalService::class)->approve($record, auth()->user(), $data['expires_at'])),
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->visible(fn (KkprlProposalEditApproval $record): bool => $record->status === 'pending' && auth()->user()?->can('ApproveEdit:KkprlProposal') === true)
                    ->form([
                        Textarea::make('reason')->label('Alasan penolakan')->required()->minLength(5),
                    ])
                    ->action(fn (KkprlProposalEditApproval $record, array $data): KkprlProposalEditApproval => app(KkprlProposalEditApprovalService::class)->reject($record, auth()->user(), $data['reason'])),
                Action::make('startSession')
                    ->label('Mulai sesi edit')
                    ->color('warning')
                    ->visible(fn (KkprlProposalEditApproval $record): bool => $record->status === 'approved'
                        && (int) $record->requested_by === (int) auth()->id()
                        && auth()->user()?->can('RunEditSession:KkprlProposal') === true)
                    ->requiresConfirmation()
                    ->action(fn (KkprlProposalEditApproval $record): mixed => app(KkprlProposalEditApprovalService::class)->startSession($record, auth()->user())),
            ]);
    }
}

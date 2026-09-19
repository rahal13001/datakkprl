<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposals\Tables;

use App\Models\KkprlProposal;
use App\Services\KkprlProposalEditApprovalService;
use App\Services\KkprlProposalRevisionService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class KkprlProposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('ticket_number')->label('Nomor tiket')->searchable()->copyable(),
                TextColumn::make('revision_label')->label('Revisi')->placeholder('utama'),
                TextColumn::make('applicant_name')->label('Pemohon'),
                TextColumn::make('institution_name')->label('Instansi/perusahaan'),
                TextColumn::make('activity_type')->label('Kegiatan')->searchable(),
                TextColumn::make('province')->label('Provinsi')->searchable(),
                TextColumn::make('regency')->label('Kabupaten/kota')->searchable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('progress_percent')->label('Progress')->suffix('%')->numeric(),
                TextColumn::make('attachments_count')->label('Lampiran')->numeric(),
                TextColumn::make('documents_count')->label('Dokumen')->numeric(),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'submitted' => 'Submitted', 'needs_revision' => 'Needs revision',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('requestRevision')
                    ->label('Minta revisi')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (KkprlProposal $record): bool => $record->status === 'submitted' && auth()->user()?->can('Review:KkprlProposal') === true)
                    ->form([
                        Textarea::make('reason')->label('Catatan revisi')->required()->minLength(5),
                    ])
                    ->requiresConfirmation()
                    ->action(function (KkprlProposal $record, array $data): void {
                        app(KkprlProposalRevisionService::class)->requestRevision($record, $data['reason'], auth()->user());
                    }),
                Action::make('requestEmergencyEdit')
                    ->label('Ajukan edit darurat')
                    ->color('danger')
                    ->icon('heroicon-o-shield-exclamation')
                    ->visible(fn (KkprlProposal $record): bool => auth()->user()?->can('RequestEdit:KkprlProposal') === true && $record->revisions()->where('status', 'draft')->exists())
                    ->form([
                        Textarea::make('reason')->label('Alasan edit darurat')->required()->minLength(5),
                        Textarea::make('scope_fields')->label('Field yang diizinkan')->helperText('Satu field per baris, misalnya bag-1.latitude.')->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (KkprlProposal $record, array $data): void {
                        $revision = $record->revisions()->where('status', 'draft')->orderByDesc('revision_number')->firstOrFail();
                        $fields = array_values(array_filter(array_map('trim', preg_split('/\R/', $data['scope_fields']) ?: [])));
                        app(KkprlProposalEditApprovalService::class)->request(
                            $record,
                            $revision,
                            auth()->user(),
                            $data['reason'],
                            ['fields' => $fields],
                        );
                    }),
                Action::make('applyEditSession')
                    ->label('Terapkan perubahan')
                    ->color('warning')
                    ->visible(fn (KkprlProposal $record): bool => $record->editSessions()
                        ->where('status', 'active')
                        ->where('started_by', auth()->id())
                        ->exists() && auth()->user()?->can('RunEditSession:KkprlProposal') === true)
                    ->form([
                        Repeater::make('changes')->label('Perubahan scoped')->minItems(1)->schema([
                            TextInput::make('field')->label('Field')->required()->placeholder('bag-1.latitude'),
                            Textarea::make('value')->label('Nilai baru')->required(),
                        ])->columns(2)->required(),
                        Textarea::make('reason')->label('Alasan perubahan')->required()->minLength(5),
                    ])
                    ->action(function (KkprlProposal $record, array $data): void {
                        $session = $record->editSessions()
                            ->where('status', 'active')
                            ->where('started_by', auth()->id())
                            ->latest('id')
                            ->firstOrFail();
                        $changes = collect($data['changes'] ?? [])->mapWithKeys(fn (array $change): array => [
                            trim((string) ($change['field'] ?? '')) => $change['value'] ?? null,
                        ])->filter(fn (mixed $value, string $field): bool => $field !== '')->all();
                        app(KkprlProposalEditApprovalService::class)->applyChanges($session, auth()->user(), $changes, $data['reason']);
                    }),
                Action::make('endEditSession')
                    ->label('Akhiri sesi edit')
                    ->color('gray')
                    ->visible(fn (KkprlProposal $record): bool => $record->editSessions()
                        ->where('status', 'active')
                        ->where('started_by', auth()->id())
                        ->exists() && auth()->user()?->can('RunEditSession:KkprlProposal') === true)
                    ->requiresConfirmation()
                    ->action(function (KkprlProposal $record): void {
                        $session = $record->editSessions()
                            ->where('status', 'active')
                            ->where('started_by', auth()->id())
                            ->latest('id')
                            ->firstOrFail();
                        app(KkprlProposalEditApprovalService::class)->endSession($session, 'cancelled', auth()->user());
                    }),
            ]);
    }
}

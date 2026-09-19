<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposals\RelationManagers;

use App\Models\KkprlProposalDocument;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Dokumen hasil generate';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chapter')->label('Bab'),
                TextColumn::make('format')->label('Format')->badge(),
                TextColumn::make('generation_status')->label('Status')->badge(),
                TextColumn::make('template_version')->label('Template'),
                TextColumn::make('snapshot_hash')->label('Snapshot hash')->copyable()->limit(16),
                TextColumn::make('attachment_manifest_hash')->label('Manifest hash')->copyable()->limit(16),
                TextColumn::make('generated_at')->label('Dibuat')->dateTime(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Unduh')
                    ->visible(fn (KkprlProposalDocument $record): bool => $record->generation_status === 'generated'
                        && auth()->user()?->can('Download:KkprlProposalDocument') === true)
                    ->url(fn (KkprlProposalDocument $record): string => route('kkprl.proposal.document.download', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}

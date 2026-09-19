<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposals\RelationManagers;

use App\Models\KkprlProposalAttachment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Lampiran privat';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chapter')->label('Bab'),
                TextColumn::make('original_name')->label('Nama file')->wrap(),
                TextColumn::make('mime_type')->label('MIME'),
                TextColumn::make('size')->label('Ukuran')->numeric()->suffix(' byte'),
                TextColumn::make('placement')->label('Posisi'),
                TextColumn::make('caption')->label('Caption')->wrap(),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Unduh')
                    ->visible(fn (): bool => auth()->user()?->can('Download:KkprlProposalAttachment') === true)
                    ->url(fn (KkprlProposalAttachment $record): string => route('kkprl.proposal.attachment.download', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}

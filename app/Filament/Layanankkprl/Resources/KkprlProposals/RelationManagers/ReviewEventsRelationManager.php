<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposals\RelationManagers;

use App\Models\KkprlProposalReviewEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ReviewEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviewEvents';

    protected static ?string $title = 'Riwayat review dan audit';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event_type')->label('Event')->badge(),
                TextColumn::make('actor_type')->label('Actor'),
                TextColumn::make('actor_id')->label('Actor ID'),
                TextColumn::make('reason')->label('Alasan')->wrap(),
                TextColumn::make('before_payload')->label('Before')->state(fn (KkprlProposalReviewEvent $record): string => self::json($record->before_payload))->wrap()->limit(500),
                TextColumn::make('after_payload')->label('After')->state(fn (KkprlProposalReviewEvent $record): string => self::json($record->after_payload))->wrap()->limit(500),
                TextColumn::make('metadata')->label('Metadata actor')->state(fn (KkprlProposalReviewEvent $record): string => self::json($record->metadata))->wrap()->limit(500),
                TextColumn::make('request_id')->label('Request ID')->copyable()->placeholder('-'),
                TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            ]);
    }

    /** @param array<string, mixed>|null $value */
    private static function json(?array $value): string
    {
        return $value === null
            ? '-'
            : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }
}

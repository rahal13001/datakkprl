<?php

namespace App\Filament\Layanankkprl\Resources\KkprlProposals\Schemas;

use App\Domain\Kkprl\ProposalChapterRules;
use App\Domain\Kkprl\ProposalFieldCatalog;
use App\Domain\Kkprl\ProposalProgress;
use App\Models\KkprlProposal;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class KkprlProposalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ringkasan proposal')->schema([
                TextEntry::make('ticket_number')->label('Nomor tiket')->copyable(),
                TextEntry::make('revision_label')->label('Revisi')->placeholder('utama'),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('progress_percent')->label('Progress')->suffix('%'),
                TextEntry::make('template_version')->label('Template'),
                TextEntry::make('updated_at')->label('Terakhir diperbarui')->dateTime(),
            ])->columns(3),
            Section::make('Bab aktif')->schema([
                TextEntry::make('bag_1_summary')->label('Bab 1')->state(fn (KkprlProposal $record): string => self::chapterText($record, 'bag-1'))->prose()->columnSpanFull(),
                TextEntry::make('bag_2_summary')->label('Bab 2')->state(fn (KkprlProposal $record): string => self::chapterText($record, 'bag-2'))->prose()->columnSpanFull(),
                TextEntry::make('bag_3_summary')->label('Bab 3')->state(fn (KkprlProposal $record): string => self::chapterText($record, 'bag-3'))->prose()->columnSpanFull(),
                TextEntry::make('bag_4_summary')->label('Bab 4')->state(fn (KkprlProposal $record): string => self::chapterText($record, 'bag-4'))->visible(fn (KkprlProposal $record): bool => app(ProposalChapterRules::class)->isRelevant('bag-4', $record->payload ?? []))->prose()->columnSpanFull(),
                TextEntry::make('bag_5_summary')->label('Bab 5')->state(fn (KkprlProposal $record): string => self::chapterText($record, 'bag-5'))->visible(fn (KkprlProposal $record): bool => app(ProposalChapterRules::class)->isRelevant('bag-5', $record->payload ?? []))->prose()->columnSpanFull(),
            ])->columns(1),
        ]);
    }

    private static function chapterText(KkprlProposal $record, string $chapter): string
    {
        $payload = $record->payload ?? [];
        $fields = match ($chapter) {
            'bag-5' => array_merge(
                ($payload['land_relation'] ?? null) === 'adjacent' ? ProposalFieldCatalog::fields('bag-5-land') : [],
                in_array($payload['has_existing_permits'] ?? false, [true, 1, '1', 'true'], true) ? ProposalFieldCatalog::fields('bag-5-permits') : [],
            ),
            default => ProposalProgress::requiredFields($chapter),
        };
        $data = [];
        foreach ($fields as $field) {
            $value = array_key_exists($field, $payload)
                ? $payload[$field]
                : (($payload[$chapter][$field] ?? null));
            $data[ProposalFieldCatalog::label($field)] = $value;
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}

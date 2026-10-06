<?php

namespace App\Filament\Layanankkprl\Resources\ProposalGuides;

use App\Filament\Layanankkprl\Resources\ProposalGuides\Pages\ManageProposalGuides;
use App\Models\ProposalGuide;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ProposalGuideResource extends Resource
{
    protected static ?string $model = ProposalGuide::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Konten Publik';

    protected static ?string $navigationLabel = 'Pedoman KKPRL';

    protected static ?string $modelLabel = 'Pedoman KKPRL';

    protected static ?string $pluralModelLabel = 'Pedoman KKPRL';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Judul Pedoman')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->label('Deskripsi')->rows(3)->columnSpanFull(),
            FileUpload::make('pdf_path')->label('PDF Pedoman')->disk('public')->directory('proposal-guides')
                ->acceptedFileTypes(['application/pdf'])->maxSize(10240)->required()->preventFilePathTampering()
                ->helperText('Maksimal 10 MB. Ganti PDF di sini; tautan publik tetap sama.')->columnSpanFull(),
            TextInput::make('sort_order')->label('Urutan')->numeric()->integer()->minValue(0)->default(0)->required(),
            Toggle::make('is_published')->label('Publikasikan')->default(false)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Judul Pedoman')->searchable()->wrap(),
            TextColumn::make('sort_order')->label('Urutan')->sortable(),
            IconColumn::make('is_published')->label('Terbit')->boolean(),
            TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i')->sortable(),
        ])->defaultSort('sort_order')->recordActions([
            EditAction::make(),
            Action::make('preview')->label('Lihat PDF')->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (ProposalGuide $record): string => route('pedoman-kkprl.pdf', $record))
                ->openUrlInNewTab()->visible(fn (ProposalGuide $record): bool => $record->is_published),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProposalGuides::route('/')];
    }
}

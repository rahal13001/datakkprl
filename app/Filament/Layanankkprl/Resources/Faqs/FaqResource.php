<?php

namespace App\Filament\Layanankkprl\Resources\Faqs;

use App\Filament\Layanankkprl\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Layanankkprl\Resources\Faqs\Pages\EditFaq;
use App\Filament\Layanankkprl\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Layanankkprl\Resources\Faqs\Schemas\FaqForm;
use App\Filament\Layanankkprl\Resources\Faqs\Tables\FaqsTable;
use App\Models\Faq;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $recordTitleAttribute = 'question';

    protected static ?string $recordRouteKeyName = 'slug';

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'id' ? 'FAQ' : 'FAQs';
    }

    public static function getModelLabel(): string
    {
        return 'FAQ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'FAQ';
    }

    public static function form(Schema $schema): Schema
    {
        return FaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FaqsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

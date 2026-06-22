<?php

namespace App\Filament\Layanankkprl\Resources\LearningMaterials\Tables;

use App\Models\LearningMaterial;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LearningMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('No')->rowIndex()->label('No'),

                TextColumn::make('title')
                    ->label('Judul Materi')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('group.title')
                    ->label('Grup')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('group.category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => $state === LearningMaterial::TYPE_PDF ? 'PDF' : 'Video')
                    ->badge()
                    ->color(fn (string $state): string => $state === LearningMaterial::TYPE_PDF ? 'danger' : 'info')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('view_count')
                    ->label('Dilihat')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('download_count')
                    ->label('Diunduh')
                    ->numeric()
                    ->sortable(),

                ToggleColumn::make('is_featured')
                    ->label('Unggulan'),

                ToggleColumn::make('is_published')
                    ->label('Publikasi'),

                TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('learning_group_id')
                    ->label('Grup')
                    ->relationship('group', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        LearningMaterial::TYPE_PDF => 'PDF',
                        LearningMaterial::TYPE_VIDEO => 'Video',
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

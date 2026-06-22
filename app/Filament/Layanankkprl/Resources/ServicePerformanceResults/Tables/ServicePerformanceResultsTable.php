<?php

namespace App\Filament\Layanankkprl\Resources\ServicePerformanceResults\Tables;

use App\Models\ServicePerformanceResult;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServicePerformanceResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No')->rowIndex()->label('No'),

                ImageColumn::make('image_path')
                    ->label('Gambar')
                    ->disk('public')
                    ->height(64)
                    ->placeholder('-'),

                TextColumn::make('display_title')
                    ->label('Judul')
                    ->searchable(['title'])
                    ->wrap(),

                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('quarter_label')
                    ->label('Triwulan')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('quarter', $direction)),

                ToggleColumn::make('is_published')
                    ->label('Publikasi'),

                TextColumn::make('published_at')
                    ->label('Tanggal Publikasi')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn (): array => ServicePerformanceResult::query()
                        ->select('year')
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->all()),

                SelectFilter::make('quarter')
                    ->label('Triwulan')
                    ->options(ServicePerformanceResult::quarterOptions()),

                TernaryFilter::make('is_published')
                    ->label('Publikasi'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

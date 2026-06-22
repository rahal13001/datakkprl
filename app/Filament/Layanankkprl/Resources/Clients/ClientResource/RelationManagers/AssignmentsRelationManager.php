<?php

namespace App\Filament\Layanankkprl\Resources\Clients\ClientResource\RelationManagers;

use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name', fn (Builder $query) => $query->where('status', true))
                    ->required()
                    ->label('Staff Assigned'),

                Forms\Components\Select::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin_mendadak' => 'Izin Mendadak',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('score')
                    ->numeric()
                    ->maxValue(100),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title') // Assignment doesn't have title, usually we show user name
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff')
                    ->searchable(),
                Tables\Columns\TextColumn::make('schedule.date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_range')
                    ->label('Waktu')
                    ->state(fn ($record) => $record->schedule ? $record->schedule->start_time.' - '.$record->schedule->end_time : '-')
                    ->size('xs'),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'scheduled' => 'Terjadwal',
                        'hadir' => 'Hadir',
                        'izin_mendadak' => 'Izin Mendadak',
                    ])
                    ->disabled(fn () => ! auth()->user()->can('UpdateAssignment')),
                Tables\Columns\TextColumn::make('score'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Assignment')
                    ->icon('heroicon-m-plus')
                    ->visible(fn () => auth()->user()->can('CreateAssignment'))
                    ->form([
                        Forms\Components\Placeholder::make('conflict_warning')
                            ->hiddenLabel()
                            ->content(function (Get $get) {
                                $scheduleIds = $get('schedule_ids') ?? [];
                                $userIds = $get('user_ids') ?? [];

                                if (empty($scheduleIds) || empty($userIds)) {
                                    return null;
                                }

                                $schedules = Schedule::whereIn('id', $scheduleIds)->get();
                                $conflicts = [];

                                foreach ($userIds as $userId) {
                                    $user = User::find($userId);
                                    if (! $user) {
                                        continue;
                                    }

                                    foreach ($schedules as $schedule) {
                                        $hasConflict = Assignment::where('user_id', $userId)
                                            ->whereHas('schedule', function ($q) use ($schedule) {
                                                $q->where('date', $schedule->date)
                                                    ->where('start_time', '<', $schedule->end_time)
                                                    ->where('end_time', '>', $schedule->start_time);
                                            })
                                            ->exists();

                                        if ($hasConflict) {
                                            $date = $schedule->date instanceof \DateTime ? $schedule->date->format('d M') : $schedule->date;
                                            $conflicts[] = '• '.e($user->name)." sibuk pada {$date} ({$schedule->start_time} - {$schedule->end_time})";
                                        }
                                    }
                                }

                                if (empty($conflicts)) {
                                    return null;
                                }

                                return new HtmlString(
                                    '<div class="text-danger-600 bg-danger-50 p-3 rounded border border-danger-200 text-sm">'.
                                    '<div class="font-bold mb-1">⚠️ Potensi Konflik Jadwal:</div>'.
                                    implode('<br>', $conflicts).
                                    '</div>'
                                );
                            })
                            ->columnSpanFull(),
                        Forms\Components\Select::make('user_ids')
                            ->label('Pilih Staff')
                            ->multiple()
                            ->options(User::where('status', true)->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('schedule_ids')
                            ->label('Pilih Jadwal')
                            ->multiple()
                            ->options(function (RelationManager $livewire) {
                                return $livewire->getOwnerRecord()->schedules
                                    ->mapWithKeys(function ($schedule) {
                                        $date = $schedule->date instanceof \DateTime
                                            ? $schedule->date->format('d M Y')
                                            : $schedule->date;

                                        return [
                                            $schedule->id => "{$date} ({$schedule->start_time} - {$schedule->end_time})",
                                        ];
                                    });
                            })
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Terjadwal',
                                'hadir' => 'Hadir',
                                'izin_mendadak' => 'Izin Mendadak',
                            ])
                            ->default('scheduled')
                            ->required(),
                    ])
                    ->action(function (array $data, AssignmentsRelationManager $livewire) {
                        foreach ($data['schedule_ids'] as $scheduleId) {
                            foreach ($data['user_ids'] as $userId) {
                                Assignment::create([
                                    'schedule_id' => $scheduleId,
                                    'user_id' => $userId,
                                    'status' => $data['status'],
                                    'score' => null,
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Assignments Created')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('UpdateAssignment')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('DeleteAssignment')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('DeleteAssignment')),
                ]),
            ]);
    }
}

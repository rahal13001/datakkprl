<?php

namespace App\Filament\Layanankkprl\Resources\Users;

use App\Filament\Layanankkprl\Resources\Users\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;

use UnitEnum;
use BackedEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
    
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Group::make()
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Informasi Akun')
                            ->description('Detail profil pengguna sistem.')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-user'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->disabled(fn (?User $record) => $record?->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')),
                            ])
                            ->columns(2),

                         \Filament\Schemas\Components\Section::make('Keamanan & Akses')
                            ->description('Atur kata sandi dan hak akses pengguna.')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (Page $livewire) => $livewire instanceof CreateRecord)
                                    ->confirmed()
                                    ->revealable()
                                    ->helperText('Biarkan kosong jika tidak ingin mengubah password.')
                                    ->disabled(fn (?User $record) => $record?->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password')
                                    ->password()
                                    ->dehydrated(false)
                                    ->required(fn (Page $livewire) => $livewire instanceof CreateRecord)
                                    ->revealable()
                                    ->disabled(fn (?User $record) => $record?->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')),

                                Forms\Components\Select::make('roles')
                                    ->label('Peran (Role)')
                                    ->relationship('roles', 'name', function (Builder $query) {
                                        // Hide super_admin if current user is not super_admin
                                        if (!auth()->user()->hasRole('super_admin')) {
                                            $query->where('name', '!=', 'super_admin');
                                        }
                                    })
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull()
                                    ->prefixIcon('heroicon-m-shield-check')
                                    ->saveRelationshipsUsing(function (\Illuminate\Database\Eloquent\Model $record, $state) {
                                        // 1. Get filtered roles (what user selected)
                                        $selectedRoleIds = collect($state)->map(fn($id) => (int)$id)->toArray();

                                        // 2. Check if we need to preserve super_admin
                                        // If the record ALREADY has super_admin, and the current user is NOT super_admin
                                        // then the current user couldn't see it to select it. We must add it back.
                                        if (!auth()->user()->hasRole('super_admin') && $record->hasRole('super_admin')) {
                                            $superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
                                            if ($superAdminRole) {
                                                $selectedRoleIds[] = $superAdminRole->id;
                                            }
                                        }

                                        // 3. Sync
                                        $record->roles()->sync($selectedRoleIds);
                                    }),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 3]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->getStateUsing(function ($record) {
                        // Get all role names
                        $roles = $record->roles->pluck('name');

                        // Filter out super_admin if the current user is NOT a super_admin
                        if (!auth()->user()->hasRole('super_admin')) {
                            $roles = $roles->reject(fn ($name) => $name === 'super_admin');
                        }

                        return $roles;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->hidden(fn (User $record) => $record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                             $records->each(function ($record) {
                                 // Prevent checking if authorized
                                 if ($record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
                                     return;
                                 }
                                 $record->delete();
                             });
                        }),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

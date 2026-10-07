<?php

namespace App\Filament\Layanankkprl\Resources\KkprlApplicants\Tables;

use App\Models\KkprlApplicant;
use App\Models\KkprlProposal;
use App\Services\KkprlApplicantAdministrationService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class KkprlApplicantsTable
{
    public static function configure(Table $table): Table
    {
        $can = fn (string $permission): bool => auth('web')->user()?->can($permission) === true;

        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('email')->label('Email')->searchable()->wrap(),
            TextColumn::make('approved_at')->label('Approval builder')->dateTime()->placeholder('Belum disetujui'),
            TextColumn::make('created_at')->label('Terdaftar')->dateTime()->sortable(),
        ])->recordActions([
            ViewAction::make(),
            Action::make('approve')->label('Setujui builder')->requiresConfirmation()
                ->visible(fn (?KkprlApplicant $record): bool => $record !== null && $record->approved_at === null && $can('Approve:KkprlApplicant'))
                ->authorize(fn (): bool => $can('Approve:KkprlApplicant'))
                ->action(fn (KkprlApplicant $record) => app(KkprlApplicantAdministrationService::class)->setApproval($record, auth('web')->user(), true)),
            Action::make('revoke')->label('Cabut approval')->color('danger')->requiresConfirmation()
                ->visible(fn (?KkprlApplicant $record): bool => $record !== null && $record->approved_at !== null && $can('Approve:KkprlApplicant'))
                ->authorize(fn (): bool => $can('Approve:KkprlApplicant'))
                ->action(fn (KkprlApplicant $record) => app(KkprlApplicantAdministrationService::class)->setApproval($record, auth('web')->user(), false)),
            Action::make('resetPassword')->label('Reset password')
                ->visible(fn (): bool => $can('ResetPassword:KkprlApplicant'))
                ->authorize(fn (): bool => $can('ResetPassword:KkprlApplicant'))
                ->modalDescription('Masukkan password baru yang disepakati dengan pemohon. Semua sesi lama pemohon akan berakhir. Sampaikan secara manual melalui kanal privat organisasi.')
                ->schema([
                    TextInput::make('password')->label('Password baru')->password()->required()->minLength(12)->maxLength(255)->confirmed()->autocomplete('new-password'),
                    TextInput::make('password_confirmation')->label('Konfirmasi password')->password()->required()->autocomplete('new-password'),
                ])
                ->action(fn (KkprlApplicant $record, array $data) => app(KkprlApplicantAdministrationService::class)->resetPassword($record, auth('web')->user(), $data['password'], $data['password_confirmation'])),
            Action::make('assignOwner')->label('Tetapkan proposal lama')
                ->visible(fn (): bool => $can('AssignOwner:KkprlApplicant'))
                ->authorize(fn (): bool => $can('AssignOwner:KkprlApplicant'))
                ->modalDescription('Verifikasi bukti kepemilikan secara manual sebelum menetapkan seluruh rantai revisi. Nomor tiket atau kesamaan email/HP saja tidak cukup.')
                ->schema([
                    TextInput::make('proposal_id')->label('ID proposal yang sudah diverifikasi')->numeric()->required()->integer()->minValue(1),
                    Textarea::make('reason')->label('Alasan penetapan')->required()->minLength(5)->maxLength(2000),
                    Textarea::make('verification_reference')->label('Referensi verifikasi bukti')->required()->minLength(5)->maxLength(2000),
                ])
                ->action(fn (KkprlApplicant $record, array $data) => app(KkprlApplicantAdministrationService::class)->assignOwner($record, KkprlProposal::findOrFail($data['proposal_id']), auth('web')->user(), $data['reason'], $data['verification_reference'])),
        ]);
    }
}

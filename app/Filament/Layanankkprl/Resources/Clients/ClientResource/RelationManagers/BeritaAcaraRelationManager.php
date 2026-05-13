<?php

namespace App\Filament\Layanankkprl\Resources\Clients\ClientResource\RelationManagers;

use App\Forms\Components\SignaturePad;
use App\Models\BeritaAcara;
use App\Services\BusinessDayService;
use App\Services\SignatureService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

class BeritaAcaraRelationManager extends RelationManager
{
    protected static string $relationship = 'beritaAcara';

    protected static ?string $title = 'Berita Acara';

    protected static ?string $modelLabel = 'Berita Acara';

    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * List of instansi (agency) options — fixed dropdown.
     */
    public static function getInstansiOptions(): array
    {
        return [
            'LPRL Sorong' => 'LPRL Sorong',
            'Sesdit PRL' => 'Sesdit PRL',
            'Direktorat Perencanaan Ruang Perairan' => 'Direktorat Perencanaan Ruang Perairan',
            'Direktorat Pemanfaatan Pesisir dan Pulau Pulau Kecil' => 'Direktorat Pemanfaatan Pesisir dan Pulau Pulau Kecil',
            'Direktorat Pemanfaatan Ruang Kolom Perairan dan Dasar Laut' => 'Direktorat Pemanfaatan Ruang Kolom Perairan dan Dasar Laut',
            'Direktorat Pengendalian Pemanfaatan Ruang Laut' => 'Direktorat Pengendalian Pemanfaatan Ruang Laut',
            'Direktorat Pembinaan Penataan Ruang Laut' => 'Direktorat Pembinaan Penataan Ruang Laut',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Berita Acara')
                    ->description('Data identitas berita acara pendampingan permohonan.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_berita_acara')
                            ->label('Nomor Berita Acara')
                            ->placeholder('Contoh: BA-001/KKPRL/IV/2026')
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('tanggal_pelaksanaan')
                            ->label('Tanggal Pelaksanaan')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('kbli')
                            ->label('KBLI')
                            ->placeholder('Kode KBLI (opsional)')
                            ->maxLength(50),
                        Forms\Components\Textarea::make('lokasi_permohonan')
                            ->label('Calon Lokasi Permohonan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Hasil Pendampingan Permohonan')
                    ->description('Tuliskan hasil pendampingan permohonan secara bebas.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\RichEditor::make('hasil_pendampingan')
                            ->label('Isi Hasil Pendampingan')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Peserta / Yang Hadir')
                    ->description('Daftar peserta yang hadir pada kegiatan pendampingan. Toggle "Penanda Tangan BA" untuk menentukan siapa yang tanda tangannya ditampilkan di dokumen.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Repeater::make('attendees')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Toggle::make('is_officer')
                                    ->label('Petugas Internal?')
                                    ->default(false)
                                    ->live()
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('is_signatory')
                                    ->label('Penanda Tangan BA?')
                                    ->helperText('Jika aktif, tanda tangan peserta ini akan ditampilkan pada dokumen BA.')
                                    ->default(true)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('nama')
                                    ->label('Nama')
                                    ->required(),
                                Forms\Components\TextInput::make('jabatan')
                                    ->label('Jabatan'),
                                Forms\Components\TextInput::make('instansi')
                                    ->label('Instansi')
                                    ->datalist(array_values(static::getInstansiOptions()))
                                    ->placeholder('Ketik atau pilih instansi'),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->placeholder('Email peserta (opsional)'),
                                Forms\Components\TextInput::make('no_hp')
                                    ->label('No. HP')
                                    ->placeholder('08xx-xxxx-xxxx (opsional)'),
                                SignaturePad::make('tanda_tangan')
                                    ->label('Tanda Tangan')
                                    ->canvasWidth(350)
                                    ->canvasHeight(120),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah Peserta')
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['nama'] ?? 'Peserta Baru')
                                . (($state['is_officer'] ?? false) ? ' (Petugas)' : '')
                                . (($state['is_signatory'] ?? true) ? '' : ' [Hadir Saja]')
                            )
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsed(),

                Section::make('Tanda Tangan Pemohon')
                    ->description('Tanda tangan calon pemohon. Bersifat opsional.')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Forms\Components\Toggle::make('hadirkan_pemohon')
                            ->label('Hadirkan Pemohon')
                            ->helperText('Jika diaktifkan, data pemohon (termasuk tanda tangan) otomatis disalin ke daftar peserta hadir sebagai peserta dan penandatangan.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($set, $get, $state, RelationManager $livewire) {
                                if ($state) {
                                    $client = $livewire->getOwnerRecord();
                                    $attendees = $get('attendees') ?? [];
                                    
                                    $exists = false;
                                    foreach($attendees as $key => $att) {
                                       if(($att['nama'] ?? '') === $client->name) {
                                           $exists = true; break;
                                       }
                                    }
                                    
                                    if (!$exists) {
                                        $uuid = (string) \Illuminate\Support\Str::uuid();
                                        $attendees[$uuid] = [
                                            'nama' => $client->name,
                                            'instansi' => $client->instance ?? '',
                                            'email' => $client->email ?? '',
                                            'no_hp' => $client->whatsapp ?? '',
                                            'is_officer' => false,
                                            'is_signatory' => true,
                                            'tanda_tangan' => $get('tanda_tangan_pemohon'),
                                        ];
                                        $set('attendees', $attendees);
                                        
                                        Notification::make()
                                            ->title('Berhasil')
                                            ->body('Pemohon ditambahkan ke Daftar Peserta.')
                                            ->success()
                                            ->send();
                                    }
                                }
                            })
                            ->dehydrated(false),
                        SignaturePad::make('tanda_tangan_pemohon')
                            ->label('Tanda Tangan Pemohon')
                            ->canvasWidth(350)
                            ->canvasHeight(120),
                    ])
                    ->columnSpanFull()
                    ->collapsed(),

                Section::make('Lampiran')
                    ->description('Lampiran pendukung berita acara.')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Forms\Components\FileUpload::make('lampiran_peta')
                            ->label('Lampiran I — Peta Hasil Plotting')
                            ->disk('local')
                            ->directory('berita-acara/peta')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(10240),
                        Forms\Components\FileUpload::make('lampiran_dokumentasi')
                            ->label('Lampiran II — Dokumentasi')
                            ->disk('local')
                            ->directory('berita-acara/dokumentasi')
                            ->image()
                            ->multiple()
                            ->maxFiles(6),
                        Forms\Components\FileUpload::make('lampiran_lainnya')
                            ->label('Lampiran III — Hal Lainnya yang Berkembang')
                            ->disk('local')
                            ->directory('berita-acara/lainnya')
                            ->multiple()
                            ->maxFiles(5),
                    ])
                    ->columns(1)
                    ->columnSpanFull()
                    ->collapsed(),

                Section::make('Status & Akses')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'completed' => 'Selesai',
                            ])
                            ->required()
                            ->default('draft'),
                        Forms\Components\Toggle::make('attendance_is_open')
                            ->label('Buka Daftar Hadir Publik')
                            ->helperText('Jika diaktifkan, peserta dapat mengakses link daftar hadir dan mengisi kehadiran mereka secara mandiri.')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_berita_acara')
                    ->label('Nomor BA')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('tanggal_pelaksanaan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('kbli')
                    ->label('KBLI')
                    ->placeholder('-'),
                TextColumn::make('attendees_count')
                    ->label('Peserta')
                    ->counts('attendees')
                    ->badge()
                    ->color('info'),
                TextColumn::make('signing_deadline')
                    ->label('Batas Tanda Tangan')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum dihitung')
                    ->color(fn (BeritaAcara $record): string =>
                        $record->isSigningExpired() ? 'danger' : 'success'
                    ),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'completed' => 'Selesai',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Berita Acara')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->processSignatures($data);
                    })
                    ->after(function (BeritaAcara $record) {
                        $this->processAttendeeSignatures($record);
                        $this->calculateAndSaveDeadline($record);
                    })
                    ->using(function (array $data, string $model): BeritaAcara {
                        // Handle auto-populating officers
                        $client = $this->getOwnerRecord();
                        $record = $client->beritaAcara()->create($data);
                        return $record;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->processSignatures($data);
                    })
                    ->after(function (BeritaAcara $record) {
                        $this->processAttendeeSignatures($record);
                        $this->calculateAndSaveDeadline($record);
                    }),
                Action::make('copyAttendanceLink')
                    ->label('Daftar Hadir Publik')
                    ->icon('heroicon-o-users')
                    ->color('warning')
                    ->modalHeading('Link Daftar Hadir Pertemuan')
                    ->modalDescription('Bagikan link master ini kepada seluruh peserta rapat. Peserta yang mengisi link ini akan otomatis masuk ke daftar peserta BA.')
                    ->modalContent(function (BeritaAcara $record) {
                        return view('filament.resources.clients.attendance-link-modal', [
                            'url' => $record->getAttendanceUrl(),
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Action::make('copySigningLinks')
                    ->label('Link Tanda Tangan')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->modalHeading('Link Tanda Tangan Peserta')
                    ->modalDescription('Bagikan link berikut ke masing-masing peserta agar mereka dapat mengisi data diri dan tanda tangan.')
                    ->modalContent(function (BeritaAcara $record) {
                        $record->load('attendees');
                        return view('filament.resources.clients.signing-links-modal', [
                            'attendees' => $record->attendees,
                            'beritaAcara' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Action::make('downloadBeritaAcara')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (BeritaAcara $record) {
                        $client = $record->client()->with([
                            'service', 'schedules.assignments.user', 'consultationLocation',
                        ])->first();

                        $signatureService = app(SignatureService::class);

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.berita-acara', [
                            'beritaAcara' => $record->load('attendees'),
                            'client' => $client,
                            'signatureService' => $signatureService,
                        ]);

                        $pdf->setPaper('a4');

                        $filename = 'Berita-Acara-' . ($record->nomor_berita_acara ?: $client->ticket_number) . '.pdf';
                        $filename = str_replace(['/', '\\'], '-', $filename);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $filename
                        );
                    }),
                DeleteAction::make(),
            ]);
    }

    /**
     * Auto-populate attendees from assigned officers when creating.
     */
    protected function getDefaultAttendees(): array
    {
        $client = $this->getOwnerRecord();
        $officers = $client->assignments()
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id')
            ->filter();

        return $officers->map(fn ($user) => [
            'nama' => $user->name,
            'jabatan' => $user->jabatan ?? '',
            'instansi' => $user->instansi ?? '',
            'is_officer' => true,
            'is_signatory' => true,
            'tanda_tangan' => null,
        ])->values()->toArray();
    }

    /**
     * Process base64 signatures into encrypted files.
     */
    protected function processSignatures(array $data): array
    {
        $signatureService = app(SignatureService::class);

        // Process applicant signature
        if (!empty($data['tanda_tangan_pemohon']) && str_starts_with($data['tanda_tangan_pemohon'], 'data:image')) {
            $data['tanda_tangan_pemohon'] = $signatureService->store($data['tanda_tangan_pemohon']);
        }

        return $data;
    }

    /**
     * Process attendee signatures after save.
     */
    protected function processAttendeeSignatures(BeritaAcara $record): void
    {
        $signatureService = app(SignatureService::class);

        foreach ($record->attendees as $attendee) {
            if (!empty($attendee->tanda_tangan) && str_starts_with($attendee->tanda_tangan, 'data:image')) {
                $path = $signatureService->store($attendee->tanda_tangan);
                $attendee->update(['tanda_tangan' => $path]);
            }
        }
    }

    /**
     * Calculate and save the signing deadline (3 business days from tanggal_pelaksanaan).
     */
    protected function calculateAndSaveDeadline(BeritaAcara $record): void
    {
        $record->refresh();
        if ($record->tanggal_pelaksanaan) {
            $deadline = $record->calculateSigningDeadline();
            $record->update(['signing_deadline' => $deadline->endOfDay()]);
        }
    }
}

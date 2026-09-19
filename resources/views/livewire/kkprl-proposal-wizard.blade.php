<main data-kkprl-wizard class="mx-auto min-h-screen w-full max-w-3xl bg-white px-4 pb-32 pt-6 text-slate-900 sm:px-6 sm:pb-6">
    <header class="mb-6">
        <p class="text-sm font-semibold uppercase tracking-wide text-amber-700">Proposal KKPRL</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">Pengisian Proposal</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Draft dapat disimpan bertahap. Proposal ini hanya dokumen pendukung review, bukan izin resmi.
        </p>
        @if ($lastSavedAt)
            <p class="mt-2 text-xs text-slate-500">Terakhir tersimpan: {{ $lastSavedAt }}</p>
        @endif
    </header>

    <section aria-labelledby="progress-title" class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="flex items-center justify-between gap-3">
            <h2 id="progress-title" class="font-semibold">Progress proposal</h2>
            <span class="text-sm font-semibold text-amber-700">
                {{ collect($chapterProgress)->sum('completed') }}/{{ collect($chapterProgress)->sum('total') }} field
            </span>
        </div>
        <div class="mt-3 grid gap-2 sm:grid-cols-5">
            @foreach ($relevantChapters as $chapter)
                @php($state = $chapterProgress[$chapter]['status'] ?? 'incomplete')
                <div class="rounded border bg-white p-2 text-xs {{ $chapter === $currentChapter ? 'border-amber-600' : 'border-slate-200' }}">
                    <div class="font-semibold">{{ str_replace('bag-', 'Bag ', $chapter) }}</div>
                    <div class="mt-1 text-slate-600">{{ $state === 'complete' ? 'Selesai' : ($state === 'error' ? 'Ada error' : 'Belum lengkap') }}</div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($locked)
        <section role="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900">
            Proposal sudah dikirim untuk ditinjau petugas. Payload dan lampiran terkunci.
            Status ini bukan penerbitan izin resmi dan tidak menggantikan OSS/e-Sea.
        </section>
    @endif

    @if ($revisionNote)
        <section role="note" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <h2 class="font-semibold">Catatan revisi petugas</h2>
            <p class="mt-1 whitespace-pre-wrap">{{ $revisionNote }}</p>
        </section>
    @endif

    @error('draft')
        <p role="alert" class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $message }}</p>
    @enderror
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            <p class="font-semibold">Periksa isian berikut:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @unless ($locked)
    <form wire:submit="saveDraft" class="space-y-6">
        @if ($currentChapter === 'bag-1')
            <section class="space-y-4" aria-labelledby="bag-1-title">
                <h2 id="bag-1-title" class="text-xl font-bold">Bag 1 — Rencana Bangunan dan Instalasi Laut</h2>
                <p class="text-sm leading-5 text-slate-600">Semua field yang ditampilkan wajib diisi saat pengiriman. Jika benar-benar tidak relevan, tulis <strong>Tidak berlaku</strong>.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'applicant_name' => 'Nama pemohon', 'applicant_position' => 'Jabatan',
                        'institution_name' => 'Nama instansi/perusahaan', 'address' => 'Alamat',
                        'identity_number' => 'Nomor KTP', 'tax_number' => 'NPWP',
                        'phone_alternative' => 'Nomor telepon/fax', 'email' => 'Email',
                        'province' => 'Provinsi', 'regency' => 'Kabupaten/kota',
                        'subdistrict' => 'Kecamatan', 'village' => 'Desa/kelurahan',
                        'water_name' => 'Nama perairan/laut', 'latitude' => 'Latitude', 'longitude' => 'Longitude',
                        'water_area' => 'Luas kebutuhan perairan', 'water_area_unit' => 'Satuan luas',
                        'depth' => 'Kedalaman kolom perairan', 'depth_unit' => 'Satuan kedalaman/datum',
                        'main_activity' => 'Kegiatan utama', 'supporting_activity' => 'Kegiatan penunjang',
                        'activity_status' => 'Kegiatan eksisting yang dimohonkan', 'schedule' => 'Jadwal kegiatan',
                        'other_matters' => 'Hal lain terkait permohonan', 'workforce_male' => 'Tenaga kerja laki-laki',
                        'workforce_female' => 'Tenaga kerja perempuan', 'investment_value' => 'Nilai investasi',
                        'investment_unit' => 'Satuan investasi', 'business_status' => 'Berusaha/nonberusaha',
                        'business_field' => 'Bidang kegiatan usaha', 'strategic_status' => 'Strategis nasional/nonstrategis',
                        'document_type' => 'Jenis dokumen PKKPRL/KKRL',
                    ] as $field => $label)
                        <label class="block text-sm font-medium">
                            {{ $label }}
                            <input id="kkprl-bag-1-{{ $field }}" name="payload.bag-1.{{ $field }}" wire:model.blur="payload.bag-1.{{ $field }}" type="text" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                        </label>
                    @endforeach
                </div>
                <label class="block text-sm font-medium">Narasi rencana tapak/site plan
                    <textarea id="kkprl-bag-1-site-plan-description" name="payload.bag-1.site_plan_description" wire:model.blur="payload.bag-1.site_plan_description" rows="4" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                </label>
                <label class="block text-sm font-medium">
                    Kegiatan mencakup reklamasi
                    <select id="kkprl-includes-reclamation" name="payload.includes_reclamation" wire:model.live="payload.includes_reclamation" required class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                        <option value="">Pilih</option>
                        <option value="1">Ya</option>
                        <option value="0">Tidak</option>
                    </select>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium">Hubungan dengan daratan
                        <select id="kkprl-land-relation" name="payload.land_relation" wire:model.live="payload.land_relation" required class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <option value="">Pilih</option><option value="adjacent">Berhimpitan</option><option value="not_adjacent">Tidak berhimpitan</option>
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Memiliki perizinan pendukung
                        <select id="kkprl-existing-permits" name="payload.has_existing_permits" wire:model.live="payload.has_existing_permits" required class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                            <option value="">Pilih</option><option value="1">Ya</option><option value="0">Tidak</option>
                        </select>
                    </label>
                </div>
            </section>
        @elseif ($currentChapter === 'bag-2')
            <section class="space-y-4" aria-labelledby="bag-2-title">
                <h2 id="bag-2-title" class="text-xl font-bold">Bag 2 — Informasi Pemanfaatan Ruang Laut</h2>
                <p class="text-sm leading-5 text-slate-600">Semua field yang ditampilkan wajib diisi saat pengiriman. Jika benar-benar tidak relevan, tulis <strong>Tidak berlaku</strong>.</p>
                <label class="block text-sm font-medium">Narasi penggunaan ruang laut
                    <textarea id="kkprl-bag-2-marine-space-use-narrative" name="payload.bag-2.marine_space_use_narrative" wire:model.blur="payload.bag-2.marine_space_use_narrative" rows="12" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                </label>
            </section>
        @elseif ($currentChapter === 'bag-3')
            <section class="space-y-4" aria-labelledby="bag-3-title">
                <h2 id="bag-3-title" class="text-xl font-bold">Bag 3 — Kondisi Terkini Perairan</h2>
                <p class="text-sm leading-5 text-slate-600">Semua field yang ditampilkan wajib diisi saat pengiriman. Jika benar-benar tidak relevan, tulis <strong>Tidak berlaku</strong>.</p>
                @foreach ([
                    'mangrove' => 'Ekosistem mangrove', 'seagrass' => 'Ekosistem lamun', 'coral_reef' => 'Ekosistem terumbu karang',
                    'current' => 'Arus', 'waves' => 'Gelombang', 'tide' => 'Pasang surut', 'bathymetry' => 'Batimetri',
                    'seabed_profile' => 'Profil dasar laut', 'socio_economic' => 'Sosial ekonomi masyarakat', 'accessibility' => 'Aksesibilitas lokasi',
                ] as $field => $label)
                    <label class="block text-sm font-medium">{{ $label }}
                        <textarea id="kkprl-bag-3-{{ $field }}" name="payload.bag-3.{{ $field }}" wire:model.blur="payload.bag-3.{{ $field }}" rows="3" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                    </label>
                @endforeach
            </section>
        @elseif ($currentChapter === 'bag-4')
            <section class="space-y-4" aria-labelledby="bag-4-title">
                <h2 id="bag-4-title" class="text-xl font-bold">Bag 4 — Persyaratan Reklamasi</h2>
                <p class="text-sm leading-5 text-slate-600">Bab reklamasi aktif. Semua field wajib diisi; jika benar-benar tidak relevan, tulis <strong>Tidak berlaku</strong>.</p>
                @foreach ([
                    'material_source_location' => 'Lokasi sumber material', 'material_image' => 'Gambar lokasi material',
                    'material_distance' => 'Jarak sumber material', 'material_distance_unit' => 'Satuan jarak material',
                    'material_volume' => 'Volume material', 'material_volume_unit' => 'Satuan volume material',
                    'material_method' => 'Metode pengambilan material', 'reclaimed_land_plan' => 'Rencana pemanfaatan lahan',
                    'reclaimed_land_map' => 'Peta lahan reklamasi', 'reclaimed_land_area' => 'Luas lahan reklamasi',
                    'reclaimed_land_area_unit' => 'Satuan luas lahan reklamasi',
                    'reclamation_method' => 'Metode pelaksanaan reklamasi', 'reclamation_schedule' => 'Jadwal reklamasi',
                    'reclamation_schedule_rows' => 'Tabel jadwal pelaksanaan',
                ] as $field => $label)
                    @if ($field === 'reclamation_schedule_rows')
                        <div class="space-y-3 rounded-md border border-slate-200 bg-white p-3">
                            <h3 class="font-semibold">{{ $label }}</h3>
                            @foreach (($payload['bag-4']['reclamation_schedule_rows'] ?? [['activity' => '', 'start_date' => '', 'end_date' => '', 'notes' => '']]) as $rowIndex => $row)
                                <div wire:key="reclamation-row-{{ $rowIndex }}" class="grid gap-2 rounded border border-slate-200 p-3 sm:grid-cols-2">
                                    <label class="text-sm font-medium">Nama kegiatan
                                        <input id="kkprl-bag-4-schedule-{{ $rowIndex }}-activity" name="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.activity" wire:model.blur="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.activity" type="text" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="text-sm font-medium">Tanggal mulai
                                        <input id="kkprl-bag-4-schedule-{{ $rowIndex }}-start-date" name="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.start_date" wire:model.blur="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.start_date" type="date" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="text-sm font-medium">Tanggal selesai
                                        <input id="kkprl-bag-4-schedule-{{ $rowIndex }}-end-date" name="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.end_date" wire:model.blur="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.end_date" type="date" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm">
                                    </label>
                                    <label class="text-sm font-medium">Catatan
                                        <input id="kkprl-bag-4-schedule-{{ $rowIndex }}-notes" name="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.notes" wire:model.blur="payload.bag-4.reclamation_schedule_rows.{{ $rowIndex }}.notes" type="text" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm">
                                    </label>
                                    @if (count($payload['bag-4']['reclamation_schedule_rows'] ?? []) > 1)
                                        <button type="button" wire:click="removeScheduleRow({{ $rowIndex }})" class="text-left text-sm font-semibold text-red-700">Hapus baris</button>
                                    @endif
                                </div>
                            @endforeach
                            <button type="button" wire:click="addScheduleRow" class="rounded border border-amber-700 px-3 py-2 text-sm font-semibold text-amber-800">Tambah baris</button>
                        </div>
                    @else
                        <label class="block text-sm font-medium">{{ $label }}
                            <textarea id="kkprl-bag-4-{{ $field }}" name="payload.bag-4.{{ $field }}" wire:model.blur="payload.bag-4.{{ $field }}" rows="3" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                        </label>
                    @endif
                @endforeach
            </section>
        @else
            <section class="space-y-4" aria-labelledby="bag-5-title">
                <h2 id="bag-5-title" class="text-xl font-bold">Bag 5 — Perizinan Lainnya</h2>
                <p class="text-sm leading-5 text-slate-600">Subbagian aktif wajib lengkap. Jika field aktif benar-benar tidak relevan, tulis <strong>Tidak berlaku</strong>.</p>
                @if (($payload['land_relation'] ?? null) === 'adjacent')
                    <h3 class="font-semibold">Bukti lahan darat</h3>
                    @foreach (['land_status' => 'Status berhimpitan dengan daratan', 'land_evidence_type' => 'Jenis bukti', 'land_acquisition_year' => 'Tahun perolehan', 'land_document' => 'Dokumen', 'land_notes' => 'Catatan'] as $field => $label)
                        <label class="block text-sm font-medium">{{ $label }}
                            <textarea id="kkprl-bag-5-{{ $field }}" name="payload.bag-5.{{ $field }}" wire:model.blur="payload.bag-5.{{ $field }}" rows="2" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                        </label>
                    @endforeach
                @endif
                @if (in_array($payload['has_existing_permits'] ?? false, [true, 1, '1', 'true'], true))
                    <h3 class="font-semibold">Perizinan yang telah dimiliki</h3>
                    @foreach (['permit_type' => 'Jenis izin', 'permit_number' => 'Nomor izin', 'permit_issuer' => 'Instansi penerbit', 'permit_year' => 'Tahun perolehan', 'permit_validity' => 'Masa berlaku', 'permit_status' => 'Status izin', 'permit_document' => 'Dokumen', 'permit_notes' => 'Catatan'] as $field => $label)
                        <label class="block text-sm font-medium">{{ $label }}
                            <textarea id="kkprl-bag-5-{{ $field }}" name="payload.bag-5.{{ $field }}" wire:model.blur="payload.bag-5.{{ $field }}" rows="2" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                        </label>
                    @endforeach
                @endif
            </section>
        @endif

        <section class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4" aria-labelledby="attachment-title">
            <h2 id="attachment-title" class="font-semibold">Lampiran {{ str_replace('bag-', 'Bab ', $currentChapter) }}</h2>
            <p class="text-xs leading-5 text-slate-600">Maksimal 10 file dan 15 MiB per bab. Format: PDF, JPG/JPEG, PNG, WebP.</p>
            <label for="kkprl-pending-files" class="block text-sm font-medium">Pilih file lampiran</label>
            <input id="kkprl-pending-files" name="pending_files[]" wire:model="pendingFiles" type="file" multiple accept="application/pdf,image/jpeg,image/png,image/webp" class="block w-full text-sm">
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-sm font-medium">Posisi
                    <select id="kkprl-attachment-placement" name="attachment_placement" wire:model="attachmentPlacement" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                        <option value="appendix">Lampiran bab</option>
                        <option value="inline">Di bawah teks acuan</option>
                    </select>
                </label>
                <label class="block text-sm font-medium">Field/teks acuan (untuk inline)
                    <input id="kkprl-attachment-anchor" name="attachment_anchor" wire:model="attachmentAnchor" list="kkprl-anchor-options" type="text" placeholder="Pilih field atau ketik frasa teks" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                    <datalist id="kkprl-anchor-options">
                        @foreach (\App\Domain\Kkprl\ProposalFieldCatalog::definitionsForPayload($attachmentChapter, $payload) as $anchorDefinition)
                            <option value="{{ $anchorDefinition['key'] }}">{{ $anchorDefinition['label'] }}</option>
                        @endforeach
                    </datalist>
                </label>
            </div>
            <label class="block text-sm font-medium">Caption/keterangan
                <input id="kkprl-attachment-caption" name="attachment_caption" wire:model="attachmentCaption" type="text" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
            </label>
            @error('attachments')
                <p role="alert" aria-live="assertive" class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            <p wire:loading wire:target="uploadAttachments" role="status" class="text-sm text-amber-800">Lampiran sedang diunggah, jangan tutup halaman.</p>
            <button type="button" wire:click="uploadAttachments" wire:loading.attr="disabled" wire:target="uploadAttachments" class="rounded-md border border-amber-700 px-4 py-2 text-sm font-semibold text-amber-800 disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="uploadAttachments">Unggah lampiran</span>
                <span wire:loading wire:target="uploadAttachments">Mengunggah...</span>
            </button>
            @if ($attachmentMessage)
                <p role="status" class="text-sm text-green-700">{{ $attachmentMessage }}</p>
            @endif

            @if ($chapterAttachments->isNotEmpty())
                <div class="space-y-3 border-t border-slate-200 pt-3">
                    <h3 class="text-sm font-semibold">Lampiran aktif bab ini</h3>
                    <ul class="space-y-2">
                        @foreach ($chapterAttachments as $attachment)
                            <li wire:key="chapter-attachment-{{ $attachment->id }}" class="flex flex-wrap items-center justify-between gap-2 rounded border border-slate-200 bg-white p-3 text-sm">
                                <span class="min-w-0 break-words">
                                    <span class="font-medium">{{ $attachment->original_name }}</span>
                                    <span class="block text-xs text-slate-500">{{ strtoupper($attachment->placement) }} · {{ number_format($attachment->size / 1024, 1) }} KB</span>
                                </span>
                                <button type="button" wire:click="deleteAttachment({{ $attachment->id }})" wire:confirm="Hapus lampiran ini?" class="rounded border border-red-700 px-3 py-2 text-xs font-semibold text-red-700">Hapus</button>
                            </li>
                        @endforeach
                    </ul>
                    <div class="grid gap-3 rounded border border-slate-200 bg-white p-3 sm:grid-cols-2">
                        <label class="block text-sm font-medium">Ganti lampiran
                            <select id="kkprl-replacement-attachment" name="replacement_attachment_id" wire:model="replacementAttachmentId" class="mt-1 block w-full rounded-md border-slate-300 px-3 py-2 text-sm shadow-sm">
                                <option value="">Pilih file</option>
                                @foreach ($chapterAttachments as $attachment)
                                    <option value="{{ $attachment->id }}">{{ $attachment->original_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-medium">File pengganti
                            <input id="kkprl-replacement-file" name="replacement_file" wire:model="replacementFile" type="file" accept="application/pdf,image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
                        </label>
                        <button type="button" wire:click="replaceAttachment" wire:loading.attr="disabled" wire:target="replaceAttachment" class="rounded border border-amber-700 px-3 py-2 text-sm font-semibold text-amber-800 disabled:cursor-wait disabled:opacity-60 sm:col-span-2">
                            <span wire:loading.remove wire:target="replaceAttachment">Ganti lampiran</span>
                            <span wire:loading wire:target="replaceAttachment">Mengganti...</span>
                        </button>
                    </div>
                </div>
            @endif
        </section>

        <footer class="flex flex-wrap justify-between gap-3 border-t border-slate-200 pt-4">
            <button type="button" wire:click="previous" @disabled($currentStep === 1) class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold disabled:opacity-50">Sebelumnya</button>
            <div class="flex gap-2">
            <button type="submit" formnovalidate wire:loading.attr="disabled" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white">
                <span wire:loading.remove>Simpan Draft</span><span wire:loading>Menyimpan...</span>
            </button>
                <button type="button" wire:click="next" @disabled($currentStep === count($relevantChapters)) class="rounded-md bg-amber-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Berikutnya</button>
                @if ($currentStep === count($relevantChapters))
                <button type="button" wire:click="submitProposal" wire:loading.attr="disabled" wire:target="submitProposal" class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="submitProposal">Kirim untuk Ditinjau</span>
                    <span wire:loading wire:target="submitProposal">Mengirim...</span>
                </button>
                @endif
            </div>
        </footer>
        @if ($saved)
            <p role="status" class="text-sm text-green-700">Draft tersimpan.</p>
        @endif
    </form>
    @endunless

    <section class="mt-6 space-y-4 rounded-lg border border-slate-200 p-4" aria-labelledby="document-title">
        <div>
            <h2 id="document-title" class="font-semibold">Dokumen per bab</h2>
            <p class="mt-1 text-xs leading-5 text-slate-600">Word dan PDF hanya dibuat untuk bab aktif yang sudah lengkap. Hasil tersimpan privat.</p>
        </div>
        @error('documents')
            <p role="alert" class="text-sm text-red-700">{{ $message }}</p>
        @enderror
        @if ($documentMessage)
            <p role="status" class="text-sm text-green-700">{{ $documentMessage }}</p>
        @endif
        <div class="space-y-3">
            @foreach ($relevantChapters as $chapter)
                @php($chapterState = $chapterProgress[$chapter] ?? ['status' => 'incomplete'])
                <div class="flex flex-wrap items-center justify-between gap-3 rounded border border-slate-200 p-3">
                    <div>
                        <div class="font-medium">{{ str_replace('bag-', 'Bab ', $chapter) }}</div>
                        <div class="text-xs text-slate-600">{{ $chapterState['status'] === 'complete' ? 'Lengkap' : 'Belum lengkap' }}</div>
                    </div>
                    @if ($chapterState['status'] === 'complete')
                        <button type="button" wire:click="generateChapterDocuments('{{ $chapter }}')" wire:loading.attr="disabled" wire:target="generateChapterDocuments" class="rounded border border-amber-700 px-3 py-2 text-sm font-semibold text-amber-800 disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="generateChapterDocuments">Buat Word + PDF</span>
                            <span wire:loading wire:target="generateChapterDocuments">Membuat dokumen...</span>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($generatedDocuments->isNotEmpty())
            <div class="space-y-2 border-t border-slate-200 pt-3">
                <h3 class="text-sm font-semibold">Hasil tersedia</h3>
                @foreach ($generatedDocuments as $document)
                    <a class="block text-sm font-medium text-amber-800 underline" href="{{ route('kkprl.proposal.document.download', $document) }}">
                        {{ str_replace('bag-', 'Bab ', $document->chapter) }} — {{ strtoupper($document->format) }}
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</main>

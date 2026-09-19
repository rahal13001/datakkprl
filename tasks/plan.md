# Implementation Plan: Pembuat Proposal KKPRL

## Overview

Membangun wizard publik mobile-first untuk membuat proposal KKPRL tanpa login. Pemohon membuat draft, menyimpan pengisian bertahap, melanjutkan dengan nomor tiket + nomor HP, mengisi bagian kondisional sesuai kegiatan, lalu mengunduh Word/PDF. Pemohon kemudian mengirim proposal dengan status `submitted` untuk ditinjau petugas melalui panel Filament `Layanankkprl`.

Fitur ini hanya sistem pendukung penyusunan dan review dokumen. Fitur ini tidak menggantikan e-Sea, OSS, atau sistem perizinan utama.

Spesifikasi utama: `docs/kkprl-proposal-builder-spec.md`.

## Status implementasi 2026-08-25

Automated implementation gates selesai: schema, access session, replaceable credential verifier boundary, wizard draft/autosave, conditional chapters, private attachment quota, layered access rate limit, payload sanitization, shared snapshot, DOCX/PDF per chapter, controlled PDF page rendering, fail-closed scan PDF rendering, BMP-to-PNG attachment rendering, portrait/landscape PDF orientation, atomic submit generation, Filament review, revisions, Shield permission split, permission catalog seeder, scoped two-person approval, approval expiry sweeper, audit trail, download authorization, stale snapshot download guard, scoped staff payload sanitization, locked-upload safe error, Livewire proposal scope lock, autosave row-lock, timeout-safe wizard denial, transactional conditional-chapter recheck for attachment upload/replace, official-style cover page with snapshot-stable year, official section-heading catalog matched to Bag 1–5 XML and golden fixture, XML-derived Roman section numbering for Bab 1/3/4/5 with Bag 2 exception in golden fixture plus direct DOCX XML verification, Indonesian field-label catalog in DOCX/PDF, all-active-chapter title matrix, incomplete-draft manual-save browser fix, dan disk `kkprl_private` tanpa serving route. Baseline PHPUnit sebelum hardening: 167 tests / 886 assertions lulus; hardening 2026-08-28 menambah sembilan belas regression test dan wajib diulang pada host dengan PHP. `npm run build` lulus. Composer audit lulus tanpa advisory. Host browser UAT terbaru juga membuktikan upload JPG inline dengan anchor/caption, private storage, dan mobile emulation 390px tanpa overflow.

Hardening follow-up 2026-08-28: identitas dokumen dan path artefak kini mencakup hash manifest lampiran, policy Filament memakai permission bisnis `Review`/`ApproveEdit`, scope edit menolak path bertingkat tak dikenal, render Livewire timeout tidak mengembalikan data proposal, dan replacement file dibersihkan bila update DB gagal. Regression tests ditambahkan; PHPUnit wajib diulang pada host dengan PHP.

Open release gates: Android/device mobile UAT, Word/LibreOffice/PDF golden-file comparison, dan operational retention approval. Keputusan MVP untuk generasi sinkron berbatas sudah dicatat di `docs/decisions/ADR-013-bounded-synchronous-document-generation.md`; threshold queue produksi tetap perlu monitoring dan sign-off operasional. Host browser smoke/UAT dengan Chrome Windows headless sudah lulus. Composer audit dan automated frontend build sudah lulus. Checklist di bawah tetap `[ ]` bila membutuhkan verifikasi manual atau persetujuan operasional.

Keputusan arsitektur:

- `docs/decisions/ADR-001-public-proposal-access.md`
- `docs/decisions/ADR-002-draft-first-conditional-wizard.md`
- `docs/decisions/ADR-003-dual-document-generation.md`
- `docs/decisions/ADR-012-kkprl-integrity-hardening.md`
- `docs/decisions/ADR-013-bounded-synchronous-document-generation.md`
- `docs/decisions/ADR-014-document-identity-includes-attachment-manifest.md`
- `docs/decisions/ADR-015-single-active-edit-approval-session.md`
- `docs/decisions/ADR-016-kkprl-legacy-artifact-fail-closed.md`

## Dependency Graph

```text
Access contract + security rules
          |
          v
Proposal schema + encrypted lookup + private files
          |
          v
Draft create/resume/autosave
          |
          v
Wizard Bag 1–3 + conditional Bag 4–5
          |
          v
Validation + snapshot
          |
          v
Word/PDF generation
          |
          v
Filament review + authorized downloads
```

## Architecture Decisions

1. Proposal menjadi domain baru. Jangan memaksa semua data proposal masuk ke workflow booking `clients` sebelum hubungan bisnisnya jelas.
2. Akses publik memakai pasangan tiket + HP, session access, rate limit, dan private files.
3. Draft-first memakai wizard Livewire dan kondisi Bag 4/5 terpusat.
4. Word dan PDF per bab dibuat dari snapshot bab yang sama.
5. Export gabungan seluruh bab ditunda dari MVP untuk mengurangi ukuran file dan beban proses.
6. Panel petugas memakai resource Filament di panel `Layanankkprl`.
7. Tidak ada API publik atau aplikasi mobile native untuk MVP.

## Tahap 0 — Review Dokumen dan Kontrak Bisnis

### Task 0.1: Review spesifikasi dan template field

**Deskripsi:** Cocokkan field pada spesifikasi dengan pemilik proses KKPRL dan enam template DOCX. Tandai field wajib, opsional, satuan, format koordinat, dan aturan PKKPRL/KKRL.

**Acceptance criteria:**

- [x] Setiap paragraf bermakna pada template memiliki mapping field atau alasan tidak dimasukkan.
- [x] Field usulan dibedakan dari field wajib regulasi.
- [x] Aturan Bag 4 dan Bag 5 dikonfirmasi.
- [x] Pertanyaan terbuka utama memiliki jawaban atau keputusan eksplisit.

**Verifikasi:** Review bersama pemilik proses; update `docs/kkprl-proposal-builder-spec.md`.

**Dependensi:** None

**File kemungkinan:** `docs/kkprl-proposal-builder-spec.md`, dokumen mapping template.

**Ukuran:** M

## Tahap 1 — Access dan Fondasi Domain

### Task 1.1: Spike generator Word/PDF

**Deskripsi:** Uji strategi DOCX dengan satu template nyata dan data contoh tersamarkan. Uji juga PDF yang sudah tersedia.

**Acceptance criteria:**

- [x] Satu contoh Bag 1 dapat dirender ke Word.
- [x] Satu contoh Bag 1 dapat dirender ke PDF.
- [x] Bagian kondisional dapat dilewati tanpa merusak dokumen.
- [x] Dependensi Composer dan risiko lisensi terdokumentasi.

**Verifikasi:** Buka hasil Word di Microsoft Word/LibreOffice dan PDF di viewer standar.

**Dependensi:** Task 0.1

**File kemungkinan:** `composer.json`, `composer.lock`, `docs/kkprl-proposal-builder-spec.md`.

**Ukuran:** M

### Task 1.2: Proposal schema, encrypted lookup, dan attachment metadata

**Deskripsi:** Tambahkan migration, model, cast/accessor, hash lookup, dan metadata lampiran. Belum membangun wizard penuh.

**Acceptance criteria:**

- [x] Nomor tiket utama dan nomor revisi unik dalam satu keluarga proposal.
- [x] Nomor HP dapat dicari exact match tanpa query plaintext.
- [x] File proposal menggunakan private disk.
- [x] Upload lampiran hanya menerima PDF, JPG/JPEG, PNG, dan WebP setelah validasi MIME/signature.
- [x] Metadata lampiran menyimpan bab, field anchor, placement inline/appendix, caption, dan urutan tampil.
- [x] Setiap bab membatasi maksimal 10 lampiran aktif dan total ukuran gabungan maksimal 15 MB.
- [x] Penggantian/penghapusan lampiran menghitung ulang kuota per bab.
- [x] Error kuota membedakan batas jumlah file dan batas total ukuran.
- [x] Field yang perlu filter petugas memiliki kolom aman dan terindeks.
- [x] Migration dapat rollback tanpa menyentuh data booking lama.

**Verifikasi:** Unit test normalisasi/hash; migration test; private storage test.

**Dependensi:** Task 0.1

**File kemungkinan:** migration baru, `app/Models/`, `app/Services/`, `tests/Unit/`, `tests/Feature/`.

**Ukuran:** L; pecah lagi jika model dan attachment menjadi dua sesi.

### Task 1.3: Create dan resume draft

**Deskripsi:** Implementasikan halaman mulai dan lanjutkan proposal dengan session access.

**Acceptance criteria:**

- [x] Pemohon dapat membuat draft dan menerima tiket.
- [x] Pemohon dapat resume dengan pasangan tiket + HP.
- [x] Pasangan salah menghasilkan error generik.
- [x] Percobaan berulang terkena rate limit.
- [x] Session access memiliki timeout dan tidak membuka proposal lain.

**Verifikasi:** Feature test create/resume/access denial/rate limit; manual dua browser.

**Dependensi:** Task 1.2

**File kemungkinan:** `routes/web.php`, `app/Livewire/`, `app/Services/`, `resources/views/livewire/`, tests.

**Ukuran:** M

### Checkpoint 1

- [ ] Spesifikasi field dan status review disetujui.
- [x] Generator spike selesai; viewer eksternal tetap menjadi verifikasi manual.
- [x] Test access dan private file lulus.
- [x] Tidak ada perubahan pada workflow booking lama.

## Tahap 2 — Wizard Proposal

### Task 2.1: Wizard shell, autosave, progress, dan draft validation

**Deskripsi:** Bangun shell Livewire mobile-first dengan navigasi langkah, simpan draft, indikator progress, loading, dan error recovery.

**Acceptance criteria:**

- [x] Pengguna dapat berpindah langkah tanpa kehilangan data.
- [x] Simpan manual dan autosave tidak membuat duplikasi.
- [x] Draft boleh incomplete.
- [x] Progress dan last saved tampil jelas di HP.
- [x] Refresh/keluar-masuk dapat memulihkan draft.

**Verifikasi:** Livewire/feature test; manual Android Chrome pada jaringan lambat.

**Dependensi:** Task 1.3

**File kemungkinan:** `app/Livewire/`, `resources/views/livewire/`, `app/Services/`, tests.

**Ukuran:** M

### Task 2.2: Bag 1 — identitas, kegiatan, lokasi, dan lampiran

**Deskripsi:** Implementasikan Bag 1 sebagai data induk proposal, termasuk input koordinat manual dan upload peta/site plan.

**Acceptance criteria:**

- [x] Field Bag 1 tersimpan sebagai draft.
- [x] Validasi koordinat, luas, dan field identitas mengikuti keputusan bisnis.
- [x] Upload peta/site plan memakai private storage dan metadata.
- [x] Data tidak hilang saat kembali ke langkah sebelumnya.

**Verifikasi:** Feature test payload/upload; manual HP portrait.

**Dependensi:** Task 2.1 dan Task 0.1

**File kemungkinan:** Livewire component/view, form rules/service, tests.

**Ukuran:** M

### Task 2.3: Bag 2 dan Bag 3 — narasi dan data pendukung

**Deskripsi:** Implementasikan informasi ruang laut serta kondisi perairan dan sekitar.

**Acceptance criteria:**

- [x] Semua subbagian Bag 2 dan Bag 3 tersedia.
- [x] Narasi panjang aman disimpan dan ditampilkan.
- [x] Upload data/gambar pendukung mengikuti rule file privat.
- [x] Validasi draft tidak memaksa field yang belum disepakati sebagai wajib.

**Verifikasi:** Feature test untuk setiap subbagian; XSS/rich-text sanitization test bila rich text dipakai.

**Dependensi:** Task 2.1 dan Task 1.2

**File kemungkinan:** Livewire component/view, validation service, tests.

**Ukuran:** M

### Task 2.4: Bag 4 dan Bag 5 kondisional

**Deskripsi:** Implementasikan pilihan reklamasi, hubungan lahan, perizinan, repeater jadwal/izin, dan aturan hide/show.

**Acceptance criteria:**

- [x] Bag 4 hanya muncul ketika reklamasi dipilih.
- [x] Bag 5 menampilkan subbagian lahan/perizinan sesuai pilihan.
- [x] Menonaktifkan kondisi tidak merender bagian tersebut pada output.
- [x] Data lama bagian kondisional ditangani dengan aturan yang disetujui, tidak dihapus diam-diam.
- [x] Jadwal reklamasi dapat disimpan sebagai beberapa baris.

**Verifikasi:** Matrix test seluruh kombinasi kondisi utama; manual tambah/hapus repeater di HP.

**Dependensi:** Task 2.1, Task 2.2, Task 1.2

**File kemungkinan:** Livewire component/view, rules/domain helper, tests.

**Ukuran:** L; pecah Bag 4 dan Bag 5 jika melebihi lima file.

### Checkpoint 2

- [x] Pemohon dapat menyelesaikan draft Bag 1–3.
- [x] Kondisi Bag 4/5 lulus matrix test.
- [x] Upload privat dan resume lulus.
- [ ] UX sudah diperiksa pada HP nyata.

## Tahap 3 — Finalisasi dan Dokumen

### Task 3.1: Final validation dan proposal snapshot

**Deskripsi:** Pisahkan validasi final dari validasi draft, lalu simpan snapshot immutable untuk proses generate.

**Acceptance criteria:**

- [x] Proposal incomplete tidak dapat dikirim sebagai `submitted`.
- [x] Field wajib mengikuti kondisi proposal.
- [x] Snapshot memiliki version/hash.
- [x] Perubahan setelah generate tidak mengubah dokumen lama.

**Verifikasi:** Unit/feature test validasi dan snapshot consistency.

**Dependensi:** Tahap 2 selesai

**File kemungkinan:** validation service, model/document service, tests.

**Ukuran:** M

### Task 3.2: Generate Word dan PDF per Bab

**Deskripsi:** Implementasikan generator Word/PDF per bab berdasarkan snapshot bab dan template version. Export gabungan seluruh bab tidak menjadi kebutuhan MVP.

**Acceptance criteria:**

- [x] Setiap bab aktif dapat menghasilkan Word dan PDF sendiri, termasuk lampiran bab yang dipetakan.
- [x] Word dan PDF per bab memuat data snapshot bab yang sama.
- [ ] Gambar, tabel, peta, dan site plan memiliki layout profesional: rasio terjaga, caption konsisten, tabel terbaca, dan page break/orientasi sesuai.
- [x] PDF lampiran memiliki strategi render/konversi yang terbukti terbaca pada Word dan PDF; synthetic smoke 2026-08-25 lulus pada Word 16 roundtrip dan Chrome PDF viewer.
- [x] Gambar inline muncul tepat di bawah teks/field anchor yang dipilih pengguna.
- [x] WebP dapat dirender pada Word/PDF melalui normalisasi salinan tanpa mengubah file asli.
- [x] Bab 1–3 dapat diekspor tanpa menunggu bab lain.
- [x] Bab 4/5 hanya dapat diekspor jika aktif dan lengkap.
- [x] Bagian tidak relevan tidak muncul.
- [x] File tersimpan private dan dapat diunduh terotorisasi.
- [x] Status generation dan error dapat ditinjau.
- [x] Retry tidak menggandakan record tanpa alasan.

**Verifikasi:** Golden-file/sample comparison dengan gambar inline, tabel, site plan, PDF lampiran, dan appendix; feature test download authorization; manual viewer check pada Word/PDF dan halaman portrait/landscape.

**Dependensi:** Task 1.1 dan Task 3.1

**File kemungkinan:** generator service, template assets, document model, routes, tests.

**Ukuran:** L; pecah per bab atau per format jika strategi generator berbeda.

### Checkpoint 3

- [x] Proposal lengkap dapat menghasilkan Word/PDF.
- [x] Proposal dengan dan tanpa Bag 4/5 menghasilkan struktur benar.
- [x] Akses download diuji lintas proposal.
- [x] Snapshot/hash dapat menjelaskan asal dokumen.
- [x] Export per bab tidak memproses bab atau lampiran yang tidak relevan.
- [ ] Lampiran bab tergabung dan tetap terbaca tanpa merusak layout isi utama.

## Tahap 4 — Filament Review

### Task 4.1: Filament resource proposal

**Deskripsi:** Tambahkan resource, table, filter, detail, lampiran, dan dokumen ke panel `Layanankkprl`.

**Acceptance criteria:**

- [x] Petugas berpermission dapat mencari dan membuka proposal.
- [x] Detail menampilkan Bag 1–5 sesuai data.
- [x] Lampiran dan dokumen memakai action privat.
- [x] Petugas tanpa permission tidak dapat mengakses resource.
- [x] Credential HP tidak bocor ke artefak DOCX/PDF; akses Filament tanpa permission ditolak dan detail reviewer tidak menampilkan credential.

**Verifikasi:** Filament policy test; manual role matrix; download test.

**Dependensi:** Task 3.2

**File kemungkinan:** resource, pages, schemas, tables, policies, tests.

**Ukuran:** L; pecah table/detail dan policy bila perlu.

### Task 4.2: Review status dan catatan

**Deskripsi:** Implementasikan status review internal hanya setelah alur kerja petugas dikonfirmasi. Status `submitted` mengunci payload, lampiran, dan snapshot dokumen. Aksi `needs_revision` membuat salinan revisi baru berstatus `draft`; proposal `submitted` asli tidak boleh ditimpa. Status apa pun tidak boleh dipresentasikan sebagai status perizinan resmi.

**Acceptance criteria:**

- [x] Status transition valid dan tercatat.
- [x] Proposal `submitted` tidak dapat diedit pemohon.
- [x] Payload, lampiran, dan snapshot dokumen tetap konsisten setelah submit.
- [x] Aksi `needs_revision` membuat salinan revisi dengan relasi dan nomor revisi.
- [x] Revisi memakai label sederhana `rev-1`, `rev-2`, dan seterusnya.
- [x] Nomor tiket utama tetap dapat dipakai untuk menemukan proposal family dan revisi aktif.
- [x] Satu proposal family tidak memiliki dua revisi aktif yang ambigu.
- [x] Proposal asli tetap dapat ditinjau setelah revisi dibuat.
- [x] Petugas default hanya dapat memberi catatan revisi.
- [x] Petugas A dapat meminta izin edit scoped dengan alasan.
- [x] Petugas B dipilih berdasarkan permission approval Filament Shield, bukan nama role hardcode; A tidak dapat menyetujui sendiri.
- [x] Role seperti pimpinan, pengelola, dan super-admin dapat menjadi B hanya jika memiliki permission approval yang sesuai.
- [x] Permission meminta edit, menyetujui approval, menjalankan sesi edit, review, dan download dipisahkan.
- [x] Role/permission actor disimpan sebagai snapshot metadata audit.
- [x] Edit darurat memakai permission khusus, alasan wajib, dan audit before/after append-only.
- [x] Approval dan edit tercatat sebagai event berbeda.
- [x] Approval hanya membuka satu sesi edit scoped.
- [x] Sesi selesai dan approval invalid setelah revisi dikirim.
- [x] Petugas B menentukan `expires_at` saat approval.
- [x] Sesi otomatis ditutup ketika approval kedaluwarsa.
- [x] Aturan edit pemohon setelah review jelas.
- [x] Catatan review tidak tercampur dengan narasi pemohon.
- [x] Semua perubahan penting memiliki audit trail.

**Verifikasi:** State transition test; audit test; manual role review.

**Dependensi:** Keputusan pertanyaan terbuka nomor 1–3.

**File kemungkinan:** model/service, Filament resource, migrations, tests.

**Ukuran:** M

## Tahap 5 — Hardening dan Release

### Task 5.1: Security, privacy, dan retention review

**Acceptance criteria:**

- [x] Rate limit diuji.
- [x] Tidak ada file sensitif di public storage.
- [x] Log tidak memuat HP/token mentah.
- [x] Session timeout dan akses silang diuji.
- [x] Kebijakan retensi draft/file terdokumentasi.

**Verifikasi:** Security checklist, route audit, storage audit, focused tests.

**Dependensi:** Tahap 4

**Ukuran:** M

### Task 5.2: Mobile QA dan release readiness

**Acceptance criteria:**

- [ ] Alur lengkap berjalan di Android Chrome.
- [ ] Upload dan autosave diuji pada jaringan lambat.
- [ ] Word/PDF dapat dibuka di perangkat umum.
- [ ] Error state memiliki instruksi yang dapat dipahami.
- [x] Dokumentasi pengguna dan petugas tersedia.

**Verifikasi:** Manual UAT dengan checklist; `rtk php artisan test`; `rtk npm run build`.

**Dependensi:** Task 5.1

**Ukuran:** M

## Risks and Mitigations

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Tiket + HP lemah sebagai credential | Akses silang/privacy | HMAC, rate limit, generic error, session timeout, private file, OTP-ready boundary |
| Format template belum memiliki field/satuan baku | Proposal salah atau tidak konsisten | Review pemilik proses sebelum final validation |
| DOCX generator tidak kompatibel dengan template | Output gagal | Spike awal dengan template nyata, pin versi library |
| Draft besar dan banyak upload | Lambat di HP/storage membesar | Private storage, metadata terpisah, upload limits, progress/loading state |
| Bagian kondisional tidak sinkron antar layer | Dokumen tidak sesuai form | Satu domain rule/helper dan matrix tests |
| Mengganggu workflow `clients` lama | Regression layanan existing | Domain proposal terpisah pada MVP, regression test booking |
| Status review belum jelas | Rework Filament | Tunda status kompleks sampai proses petugas dikonfirmasi |

## Parallelization

Setelah Task 1.2 selesai, Task 1.3 dan spike generator dapat berjalan paralel. Setelah wizard shell stabil, Bag 1–3 dan desain Filament dapat dikerjakan paralel dengan write set berbeda. Migration, status domain, dan generator snapshot tetap berurutan.

## Open Questions

Ikuti daftar pada `docs/kkprl-proposal-builder-spec.md`, terutama format koordinat, aturan wajib, strategi DOCX, dan retensi data. Status final serta hak edit petugas berbasis Filament Shield sudah diputuskan dan tidak boleh dirancang ulang sebagai role system baru.

## Checkpoint Complete

- [ ] Semua success criteria pada spesifikasi terpenuhi.
- [ ] Test otomatis lulus setelah hardening (baseline 167 test lulus; 15 regression test tambahan masih menunggu rerun pada host PHP).
- [ ] UAT HP lulus.
- [x] Security/privacy review teknis lulus; residual risk operasional dan sign-off retensi tetap terbuka. Lihat `docs/kkprl-security-review.md`.
- [ ] Petugas menyetujui alur review.
- [ ] Dokumen Word/PDF disetujui berdasarkan template.

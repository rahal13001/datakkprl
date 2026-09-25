# Spesifikasi: Pembuat Proposal KKPRL

## Status Dokumen

- Status: Disetujui (Updated with AI Integration Plan)
- Tanggal: 2026-08-24
- Produk: Laravel + Livewire + Filament
- Sumber format: `docs/template/*.docx`
- Pengguna utama: pengguna jasa dan pelaku usaha kecil
- Panel petugas: Filament `Layanankkprl`

Dokumen ini menjadi sumber kebenaran untuk MVP pembuat proposal KKPRL. Perubahan kebutuhan harus memperbarui dokumen ini sebelum kode diubah.

## 1. Tujuan

Menyediakan wizard formulir publik yang ringan di HP untuk membantu pemohon membuat proposal KKPRL secara bertahap, menyimpan draft, melanjutkan pengisian tanpa akun, lalu mengunduh proposal dalam format Word dan PDF.

Petugas tetap dapat membuka, memeriksa, dan meninjau proposal dari panel Filament.

Fitur ini adalah sistem pendukung penyusunan dan review dokumen. Fitur ini bukan sistem perizinan utama dan tidak menggantikan e-Sea, OSS, atau kanal resmi lain untuk pengajuan dan penerbitan perizinan.

## 2. Capability Map

| Module ID | Tanggung jawab | Bergantung pada |
|---|---|---|
| `proposal-access` | Membuat tiket, melanjutkan draft dengan nomor tiket + nomor HP, session access, rate limit | — |
| `proposal-draft` | Membuat draft, autosave, progress, status, pemulihan data | `proposal-access` |
| `proposal-form` | Form Bag 1–5, validasi, kondisi bagian, upload lampiran | `proposal-draft` |
| `proposal-document` | Render proposal ke Word/PDF berdasarkan snapshot data | `proposal-draft`, `proposal-form` |
| `proposal-review` | Daftar, detail, filter, dan peninjauan petugas di Filament | `proposal-draft`, `proposal-document` |
| `proposal-ai-chat` | Asisten AI (Gemini Flash) per bab untuk tanya jawab, ekstraksi payload JSON, baca lampiran non-sensitif | `proposal-draft` |

Urutan build: `proposal-access` → `proposal-draft` → `proposal-form` (Bab 1) & `proposal-ai-chat` (Bab 2-5) → `proposal-document` → `proposal-review`.

## 3. Keputusan Produk yang Sudah Disetujui

1. Pemohon tidak perlu membuat akun.
2. Pemohon melanjutkan proposal dengan pasangan `nomor tiket + nomor HP`.
3. OTP belum digunakan pada MVP, tetapi desain tidak boleh menghambat penambahan OTP.
4. Draft disimpan bertahap karena pengisian panjang dan data pendukung mungkin belum tersedia sekaligus.
5. Form harus nyaman digunakan dari HP dan tetap ringan.
6. Koordinat diisi manual.
7. Peta, site plan, dan dokumen pendukung diunggah sebagai lampiran.
8. Bag 1–3 selalu tersedia.
9. Bag 4 hanya muncul jika kegiatan mencakup reklamasi.
10. Bag 5 muncul sesuai kebutuhan bukti lahan darat atau perizinan pendukung.
11. Setelah pengisian selesai, pemohon dapat mengunduh Word dan PDF.
12. Petugas dapat meninjau proposal melalui Filament.
13. Dokumen dapat diekspor dan diunduh per bab, bukan hanya sebagai satu file gabungan.
14. **Integrasi AI:** Pengisian Bab 2, 3, 4, dan 5 dibantu oleh AI Agent (Gemini Flash) dengan antarmuka Chatbot (Blended UI).
15. **Pemisahan Data Sensitif:** Bab 1 (Identitas, NIK, NPWP) murni form statis tanpa campur tangan AI.
16. **Otorisasi AI:** AI hanya menyarankan *draft payload* JSON. Pengguna wajib menekan konfirmasi sebelum AI menyimpan data ke database.
17. **AI Vision:** Lampiran non-sensitif (Peta, Foto) dapat dianalisis AI untuk membantu melengkapi narasi, lampiran sensitif (KTP) *bypass* AI.
18. **Persistensi Chat:** Riwayat percakapan AI disimpan agar pengguna dapat melanjutkan sesi sebelumnya tanpa mengulang.

## 4. Asumsi yang Dipakai

1. Alur publik memakai Livewire, bukan aplikasi mobile native atau API terpisah.
2. Data proposal disimpan di database Laravel yang sudah berjalan.
3. Lampiran proposal memakai private storage yang sudah digunakan aplikasi untuk data sensitif.
4. Nomor tiket dibuat sistem dan unik.
5. Nomor HP dinormalisasi sebelum pencarian, misalnya menghapus spasi dan menyamakan format negara.
6. Setelah verifikasi pasangan berhasil, sistem membuat session access sehingga nomor HP tidak dikirim pada setiap langkah.
7. File hasil generate dan lampiran diperlakukan sebagai data privat.
8. Template Word yang ada menjadi referensi format, bukan otomatis dianggap siap dipakai sebagai template merge.
9. Tipe lampiran yang didukung MVP dibatasi pada PDF, JPG/JPEG, PNG, dan WebP.
10. Setiap bab memiliki kuota maksimal 10 lampiran dengan total ukuran gabungan maksimal 15 MB; validator teknis memakai 15 MiB (15.728.640 byte / 15.360 KB).

Asumsi nomor 5–8 harus diverifikasi saat desain teknis dan implementasi.

## 5. Pengguna dan Hak Akses

### 5.1 Pemohon publik

Pemohon dapat membuat proposal, menyimpan draft, melanjutkan dengan pasangan tiket + HP, mengubah draft yang belum dikunci, mengelola lampiran miliknya, dan mengunduh Word/PDF miliknya.

Pemohon tidak dapat melihat proposal lain, mencari proposal hanya dengan nomor tiket, mengakses URL storage yang ditebak, atau mengubah proposal setelah status `submitted`.

### 5.2 Petugas

Petugas yang berpermission melalui Filament Shield dapat melihat daftar proposal, mencari berdasarkan field aman, membuka detail, meninjau kelengkapan, mengunduh file sesuai hak akses, dan mengelola status atau catatan review setelah aturan status ditetapkan. Filament Shield yang sudah ada menjadi sumber kebenaran role dan permission; sistem tidak membuat role baru khusus KKPRL.

Secara default, petugas hanya memberi catatan revisi. Petugas tidak mengubah data pemohon pada salinan revisi.

Dalam kondisi darurat atau keterpaksaan yang disetujui organisasi, petugas tertentu dapat meminta izin edit salinan revisi. Perubahan tersebut wajib:

- Dilakukan pada salinan revisi, bukan proposal `submitted` asli.
- Diminta oleh petugas A dengan alasan dan target revisi yang jelas.
- Disetujui petugas B yang memiliki permission approval khusus.
- Menghasilkan izin edit yang scoped pada proposal/revisi terkait dan satu sesi edit, bukan akses edit global.
- Memiliki waktu kedaluwarsa yang ditentukan Petugas B saat approval.
- Tidak dapat dilakukan petugas A sebelum approval berhasil.
- Meminta alasan perubahan.
- Mencatat petugas, waktu, field sebelum, field sesudah, dan alasan.
- Menandai bahwa perubahan dilakukan oleh petugas.
- Masuk ke riwayat review yang dapat dilihat oleh petugas berwenang.
- Tidak menghapus catatan atau nilai sebelumnya.

Permission edit darurat harus terpisah dari permission review biasa. Permission approval juga harus terpisah dari permission meminta edit dan permission menjalankan sesi edit.

Role tidak boleh di-hardcode sebagai syarat Petugas B. Petugas B adalah user Filament yang memiliki permission approval edit sesuai konfigurasi Shield. Role seperti `pimpinan`, `pengelola`, atau `super-admin` hanya contoh role yang dapat diberi permission tersebut; role lain juga boleh menjadi Petugas B bila diberi permission yang sama. Implementasi harus memeriksa permission aktual melalui Shield/authorization Laravel, bukan membandingkan nama role.

Permission minimum yang perlu dipetakan ke konfigurasi Shield (nama final mengikuti konvensi aplikasi):

- permission melihat/review proposal;
- permission meminta edit darurat;
- permission menyetujui atau menolak approval edit;
- permission menjalankan sesi edit setelah approval;
- permission mengunduh dokumen dan lampiran.

Daftar kandidat Petugas B hanya boleh berisi user yang memiliki permission approval. User yang meminta approval tidak boleh menyetujui permintaannya sendiri walaupun memiliki permission tinggi atau akses super-admin. Periksa konfigurasi bypass super-admin yang sudah ada, tetapi aturan separation of duties ini tetap wajib ditegakkan pada level domain.

Aktivitas penting petugas harus tercatat memakai audit log atau mekanisme audit aplikasi yang sudah ada.

## 6. Alur Pengguna

### 6.1 Membuat proposal baru

1. Pemohon membuka halaman pembuat proposal KKPRL.
2. Pemohon memilih **Buat Proposal Baru**.
3. Sistem menjelaskan penyimpanan draft dan cara melanjutkan.
4. Pemohon mengisi identitas minimum, termasuk nomor HP.
5. Sistem membuat nomor tiket unik dan draft awal.
6. Sistem menampilkan nomor tiket dengan instruksi untuk menyimpannya.
7. Pemohon masuk ke wizard Bag 1–3.

Nomor tiket harus dapat disalin dan tidak boleh menjadi satu-satunya rahasia akses.

### 6.2 Melanjutkan draft

1. Pemohon membuka **Lanjutkan Proposal**.
2. Pemohon mengisi nomor tiket dan nomor HP.
3. Sistem menormalisasi HP, mencari proposal family berdasarkan nomor tiket utama, lalu membuat session access.
4. Jika benar dan ada revisi aktif berstatus `draft`, sistem membuka revisi aktif tersebut.
5. Jika belum ada revisi aktif, sistem membuka proposal utama atau menampilkan status terakhir yang tersedia.
6. Jika pasangan salah, sistem menampilkan pesan generik tanpa membocorkan field mana yang salah.
7. Sistem membatasi percobaan berulang.

Nomor tiket tidak boleh menjadi satu-satunya otorisasi pada URL publik atau download.

### 6.3 Pengisian bertahap

- Setiap langkah memiliki tombol **Simpan Draft**.
- Sistem dapat autosave ketika pengguna berpindah langkah atau menyelesaikan bagian utama.
- Autosave harus idempotent dan tidak membuat record duplikat.
- Pengguna dapat kembali ke langkah sebelumnya.
- Progress membedakan langkah selesai, belum lengkap, dan memiliki error.
- Draft dapat disimpan walaupun bagian yang belum wajib masih kosong.
- Validasi final lebih ketat daripada validasi draft.

### 6.4 Penyelesaian dan dokumen

1. Sistem menjalankan validasi final berdasarkan jenis kegiatan dan pilihan Bag 5.
2. Sistem menampilkan ringkasan.
3. Pemohon mengonfirmasi data.
4. Sistem membuat snapshot data dan manifest lampiran untuk bab yang dipilih.
5. Sistem menyusun isi bab, gambar/tabel, dan lampiran bab tersebut ke Word dan PDF dari snapshot yang sama.
6. Pemohon dapat mengunduh kedua format secara terpisah.
7. Draft tetap tersimpan untuk ditinjau petugas.

Jika satu format gagal dibuat, sistem tidak boleh menyatakan proses selesai penuh. Pengguna harus mendapat pesan jelas dan dapat mencoba ulang.

## 7. Struktur Form Berdasarkan Template

### 7.1 Header dokumen

- Judul proposal.
- Nama perusahaan/perseorangan/instansi.
- Jenis dokumen PKKPRL/KKRL sesuai aturan layanan.
- Tahun proposal.
- Nomor tiket internal, bila boleh tampil pada dokumen.

Pilihan istilah `PKKPRL/KKRL` pada template masih ambigu dan perlu validasi bisnis.

### 7.2 Bag 1 — Rencana Bangunan dan Instalasi Laut

Bagian wajib untuk semua proposal. Semua field Bag 1 yang ditampilkan dan berasal dari template wajib diisi sebelum Bag 1 dapat diekspor atau proposal dapat dikirim. Lampiran yang diwajibkan oleh format juga harus tersedia.

#### Informasi pemohon

- Nama pemohon.
- Jabatan, bila relevan.
- Nama instansi/perusahaan.
- Alamat.
- Nomor KTP, bila diwajibkan.
- NPWP.
- Nomor HP.
- Nomor telepon/fax, bila ada.
- Email.

#### Lokasi administratif dan ruang laut

- Provinsi.
- Kabupaten/kota.
- Kecamatan.
- Desa/kelurahan.
- Nama perairan/laut.
- Latitude dan longitude manual.
- Luas kebutuhan perairan dan satuannya.
- Kedalaman kolom perairan dan satuannya.

Catatan: template menulis `meter dpl` untuk kedalaman kolom perairan. Satuan atau datum harus dikonfirmasi sebelum validasi dikunci.

#### Rencana kegiatan

- Kegiatan utama dan penunjang.
- Kegiatan eksisting yang dimohonkan.
- Jadwal pelaksanaan kegiatan utama dan pendukung.
- Hal lain terkait permohonan.
- Jumlah tenaga kerja, dipisahkan sesuai gender bila diwajibkan.
- Nilai investasi dan satuannya.

#### Jenis kegiatan dan tapak

- Berusaha atau nonberusaha.
- Bidang kegiatan usaha, jika berusaha.
- Strategis nasional atau nonstrategis nasional.
- Narasi rencana tapak/site plan.
- Upload site plan.
- Upload gambar bangunan/instalasi laut.
- Upload fasilitas penunjang, bila ada.
- Upload peta lokasi.

### 7.3 Bag 2 — Informasi Pemanfaatan Ruang Laut

Bagian wajib untuk semua proposal. Narasi utama Bag 2 wajib diisi sebelum Bag 2 dapat diekspor atau proposal dapat dikirim.

Semua field Bag 2 yang ditampilkan pada form wajib diisi. Jika field tambahan seperti sumber atau periode informasi ditampilkan pada MVP, field tersebut juga wajib.

- Narasi penggunaan ruang laut di sekitar area permohonan.
- Sumber informasi, bila tersedia.
- Periode/tanggal informasi, bila tersedia.
- Lampiran pendukung, bila ada.

Template hanya menyebut narasi. Field sumber dan periode adalah usulan sampai dikonfirmasi.

### 7.4 Bag 3 — Kondisi Terkini Perairan dan Sekitarnya

Bagian wajib untuk semua proposal. Semua subbagian Bag 3 yang ditampilkan wajib diisi sebelum Bag 3 dapat diekspor atau proposal dapat dikirim.

- Ekosistem: mangrove, lamun, terumbu karang.
- Hidro-oseanografi: arus, gelombang, pasang surut, batimetri.
- Profil dasar laut.
- Karakteristik sosial ekonomi masyarakat.
- Aksesibilitas lokasi.

Setiap subbagian minimal menyediakan narasi dan upload data/gambar pendukung sesuai field pada form. Metode survei, tanggal data, skala peta, dan sumber data adalah field tambahan; jika ditampilkan pada MVP, field tersebut juga wajib diisi berdasarkan keputusan ini.

Untuk Bab 1–3, field yang ditampilkan pada form tidak boleh dibiarkan kosong. Jika suatu field tidak relevan untuk jenis pemohon tertentu, form harus menyediakan pilihan eksplisit seperti **Tidak berlaku** atau menyembunyikannya berdasarkan aturan yang terdokumentasi.

### 7.5 Bag 4 — Persyaratan Reklamasi

Hanya tampil jika kegiatan mencakup reklamasi. Jika tampil, semua field Bag 4 wajib diisi sebelum Bag 4 dapat diekspor atau proposal dapat dikirim.

- Lokasi pengambilan sumber material.
- Gambar lokasi pengambilan material.
- Jarak lokasi material ke lokasi reklamasi.
- Jumlah/volume kebutuhan material dan satuannya.
- Metode pengambilan material.
- Rencana pemanfaatan lahan reklamasi.
- Peta dan luas lahan reklamasi.
- Metode pelaksanaan reklamasi dari aspek teknis, pengambilan material, dan penimbunan.
- Jadwal pelaksanaan reklamasi.
- Tabel jadwal pelaksanaan.

Template meminta tabel jadwal tetapi tidak menyediakan struktur. Usulan minimal: nama kegiatan, tanggal mulai, tanggal selesai, dan catatan.

### 7.6 Bag 5 — Perizinan Lainnya

Bagian ini dikendalikan oleh pilihan pemohon. Subbagian yang dipilih wajib lengkap sebelum dapat diekspor atau proposal dapat dikirim. Subbagian yang tidak dipilih tidak wajib dan tidak dirender.

#### Bukti lahan darat

Tampil jika area berhimpitan dengan daratan atau pemohon memilih memiliki dokumen lahan.

- Status berhimpitan dengan daratan.
- Jenis bukti kepemilikan/penguasaan.
- Tahun perolehan.
- Upload dokumen atau screenshot.
- Catatan.

#### Perizinan yang telah dimiliki

Tampil jika pemohon memilih memiliki izin pendukung.

- Jenis izin.
- Nomor izin, jika tersedia.
- Instansi penerbit, jika tersedia.
- Tahun perolehan.
- Masa berlaku, jika tersedia.
- Status izin.
- Upload dokumen atau screenshot.
- Catatan.

Field tambahan belum boleh dibuat wajib sebelum dikonfirmasi.

## 8. Aturan Kondisional

| Kondisi | Dampak |
|---|---|
| Semua proposal | Bag 1, Bag 2, Bag 3 tampil |
| `includes_reclamation = true` | Bag 4 tampil dan wajib saat validasi final |
| `land_relation = adjacent` | Subbagian bukti lahan darat tampil |
| `has_existing_permits = true` | Subbagian perizinan tampil |
| Tidak ada reklamasi | Bag 4 tidak dirender ke form maupun dokumen |
| Tidak ada kebutuhan Bag 5 | Bag 5 tidak dirender ke form maupun dokumen |

Aturan yang sama wajib dipakai oleh Livewire validation, ringkasan, generator Word, generator PDF, dan tampilan Filament.

### Kelengkapan per bab

- Bab 1, Bab 2, dan Bab 3 wajib lengkap untuk semua proposal.
- Bab 4 wajib lengkap hanya jika reklamasi dipilih.
- Bab 5 wajib lengkap hanya untuk subbagian lahan/perizinan yang dipilih.
- Bab yang belum lengkap tidak dapat diekspor.
- Proposal tidak dapat berstatus `submitted` sebelum seluruh bab yang relevan lengkap.

## 9. Draft dan Status

### Status minimum

- `draft`: masih diisi atau dapat diperbaiki.
- `submitted`: pemohon sudah menyelesaikan pengisian, dokumen berhasil dibuat, dan proposal dikirim untuk ditinjau petugas.

`submitted` tidak berarti izin telah diterbitkan, disetujui secara hukum, atau menggantikan status pada e-Sea/OSS.

Saat status berubah menjadi `submitted`, payload proposal, lampiran, dan snapshot dokumen dikunci untuk menjaga konsistensi. Pemohon tidak dapat melakukan perubahan mendadak setelah submit.

Jika diperlukan koreksi, mekanisme revisi berupa aksi eksplisit yang tercatat; proposal awal tidak dibuka kembali secara diam-diam.

Status `needs_revision` digunakan sebagai instruksi review untuk membuat salinan revisi baru. Status ini tidak mengubah payload, lampiran, atau snapshot proposal `submitted` asli. Status `in_review`, `approved`, atau `rejected` hanya boleh dipakai jika proses internal pendampingan sudah ditetapkan; status tersebut bukan status perizinan resmi.

### Revisi proposal

1. Proposal awal berstatus `submitted` dan tetap immutable.
2. Petugas memilih aksi **Minta Revisi** dan menulis catatan revisi.
3. Sistem membuat salinan proposal baru yang terhubung ke proposal awal.
4. Salinan baru berstatus `draft` dan mendapat label sederhana `rev-1`, `rev-2`, dan seterusnya.
5. Nomor tiket utama tetap sama untuk seluruh proposal family.
6. Pemohon cukup memasukkan nomor tiket utama + nomor HP; sistem melacak revisi aktif tanpa error.
7. Pemohon mengakses dan melengkapi salinan revisi sesuai mekanisme akses yang ditetapkan.
8. Dalam kondisi khusus, petugas A meminta izin edit dengan alasan dan target revisi.
9. Petugas B yang memiliki permission approval menyetujui atau menolak permintaan.
10. Jika disetujui, sistem membuat satu sesi edit scoped untuk petugas A.
11. Petugas A dapat melakukan beberapa perubahan yang diizinkan selama sesi aktif; setiap perubahan tetap dicatat before/after.
12. Saat salinan dikirim, salinan berubah menjadi `submitted`, sesi edit selesai, dan approval tidak dapat digunakan lagi.
13. Proposal awal, seluruh snapshot, lampiran, approval, sesi edit, dan catatan review tetap dapat ditelusuri.

Sistem tidak boleh menimpa data proposal awal dengan data revisi.

Sistem sebaiknya menyimpan langkah terakhir, persentase progress, waktu autosave terakhir, waktu generate terakhir, versi struktur form, dan versi template dokumen. Progress adalah indikator UX, bukan bukti kelengkapan regulasi.

## 10. Keamanan dan Privasi

Pasangan nomor tiket + nomor HP lebih mudah ditebak daripada akun dengan password kuat. Karena OTP belum digunakan, MVP wajib menerapkan mitigasi berikut:

- Simpan nomor HP terenkripsi jika mengikuti pola data protection aplikasi.
- Simpan hash/HMAC nomor HP untuk exact lookup tanpa query plaintext.
- Jangan menyimpan nomor HP sebagai password plaintext.
- Jangan menaruh nomor HP pada URL, nama file, atau pesan error.
- Gunakan session access setelah verifikasi pasangan.
- Terapkan rate limit pada percobaan melanjutkan proposal.
- Gunakan respons error generik untuk pasangan yang salah.
- Terapkan idle timeout dan invalidasi session access.
- Cegah akses file langsung melalui URL publik.
- Validasi MIME type, ekstensi, ukuran, dan nama file upload.
- Sanitasi nama file dan simpan dengan nama acak.
- Catat aktivitas akses penting tanpa menyimpan rahasia mentah.
- Gunakan abstraksi verifikasi akses agar OTP dapat ditambahkan kemudian.

Security review khusus wajib dilakukan sebelum fitur dibuka publik.

## 11. Model Data Awal yang Diusulkan

Model final harus mengikuti pola migrasi dan encryption yang sudah ada. Rancangan awal:

### `kkprl_proposals`

- `id`.
- `root_proposal_id`, untuk menghubungkan semua record dalam satu proposal family.
- `revision_of_id`, nullable, untuk menghubungkan salinan revisi dengan proposal sebelumnya.
- `revision_number`, dengan `0` untuk proposal utama.
- `ticket_number` utama, sama untuk seluruh proposal family.
- `revision_label`, `null` untuk utama lalu `rev-1`, `rev-2`, dan seterusnya.
- `status`.
- `current_step`.
- `progress_percent`.
- `applicant_type`.
- `applicant_name`.
- `institution_name`.
- `phone_encrypted` atau pola terenkripsi yang setara.
- `phone_hash` untuk exact lookup.
- `email_encrypted`, bila field email disimpan dalam model terproteksi.
- Field inti administratif yang perlu filter petugas.
- `payload` JSON untuk data Bag 1–5 yang masih berkembang, atau tabel terpisah jika kebutuhan query sudah jelas.
- `last_saved_at`.
- `generated_at`.
- `form_version`.
- `template_version`.
- `created_at`, `updated_at`.

### `kkprl_proposal_chats`
Tabel baru untuk menyimpan persistensi obrolan AI per proposal/bab:
- `id`.
- `proposal_id`.
- `chapter` (bag-2, bag-3, dst).
- `messages` (JSON/Array berisi riwayat pesan pengguna & AI).
- `last_interaction_at`.
- `created_at`, `updated_at`.

### `kkprl_proposal_attachments`

- `id`.
- `proposal_id`.
- `chapter` (`bag-1` sampai `bag-5`).
- `section`.
- `field`.
- `attachment_role` seperti `image`, `map`, `site_plan`, atau `supporting_document`.
- `placement` (`inline` atau `appendix`).
- `anchor_key`, nullable untuk lampiran appendix; berisi field/teks acuan tempat gambar inline ditempatkan.
- `display_order` untuk urutan deterministik saat digabung ke dokumen.
- `caption`, nullable bila nama file cukup sebagai keterangan.
- `original_name` yang sudah disanitasi untuk tampilan.
- `storage_disk`.
- `storage_path` privat.
- `mime_type`.
- `size`.
- `checksum`, jika diperlukan.
- `uploaded_at`.
- `deleted_at`, bila soft delete diperlukan.

Lampiran harus memiliki pemetaan bab, bagian, field sumber, posisi (`inline` atau `appendix`), urutan tampil, dan caption opsional. File tetap disimpan privat; penggabungan ke dokumen dilakukan saat generate, bukan dengan menjadikan storage publik.

Kuota lampiran dihitung per bab dan mencakup seluruh file aktif pada bab tersebut, baik gambar inline, gambar appendix, peta, site plan, maupun PDF. Maksimum 10 file dan total ukuran gabungan 15 MB per bab. Penggantian file menghitung ulang jumlah dan ukuran; file lama yang diganti tidak boleh tetap membebani kuota jika sudah tidak aktif. Validasi memakai ukuran file aktual dan signature/MIME yang tervalidasi, bukan hanya nama ekstensi.

Jika kuota terlampaui, upload atau penggantian ditolak dengan pesan yang menyebut apakah masalahnya jumlah file atau total ukuran. Draft yang sudah tersimpan tetap aman; pengguna harus menghapus atau mengganti lampiran sebelum melanjutkan. Aturan yang sama berlaku pada salinan revisi.

Untuk gambar inline, alur tampil default adalah: teks/field acuan → jarak visual → gambar → caption/keterangan. Pengguna memilih field atau blok teks tempat gambar ditempatkan, sehingga gambar desain instalasi dapat muncul langsung di bawah uraian yang menjelaskannya. Gambar appendix tetap ditempatkan pada bagian lampiran bab.

### `kkprl_proposal_documents`

- `id`.
- `proposal_id`.
- `format` (`docx` atau `pdf`).
- `chapter` (`bag-1`, `bag-2`, `bag-3`, `bag-4`, atau `bag-5`).
- `generation_scope`, selalu `chapter` untuk MVP.
- `template_version`.
- `snapshot_hash`.
- `attachment_manifest_hash`.
- `storage_path` privat.
- `generated_at`.
- `generation_status`.
- `error_code` tanpa detail rahasia.

### `kkprl_proposal_review_events`

- `id`.
- `proposal_id`.
- `event_type` seperti `submitted`, `revision_requested`, `edit_approval_requested`, `edit_approved`, `edit_session_started`, `staff_edit`, `edit_session_completed`, atau `edit_rejected`.
- `actor_type` seperti `applicant` atau `staff`.
- `actor_id`, bila actor adalah petugas internal.
- `reason`, wajib untuk `staff_edit`.
- `before_payload` atau diff sebelum perubahan.
- `after_payload` atau diff sesudah perubahan.
- `metadata` untuk label revisi, request ID, dan konteks teknis.
- `created_at`.

Riwayat review bersifat append-only. Event lama tidak boleh dihapus atau ditimpa.

### `kkprl_proposal_edit_approvals`

- `id`.
- `proposal_id` dan `revision_id`.
- `requested_by` petugas A.
- `approved_by`, nullable sampai diproses petugas B.
- `status`: `pending`, `approved`, `rejected`, `expired`, atau `used`.
- `reason` dari petugas A.
- `scope`, berisi target field/section yang boleh diedit.
- `requested_at`.
- `approved_at`, nullable.
- `expires_at`, wajib dan ditentukan Petugas B saat approval.
- `used_at`, nullable.
- `approval_event_id` untuk menghubungkan audit approval.
- `edit_session_id`, nullable sebelum approval dipakai.
- `session_started_at`, nullable.
- `session_finished_at`, nullable.

Petugas A tidak boleh menyetujui permintaannya sendiri. Approval harus berasal dari actor berbeda yang memiliki permission approval melalui Filament Shield. Role dan permission actor saat request/approval dibuat dicatat sebagai metadata audit agar riwayat tetap jelas walaupun konfigurasi role berubah kemudian.

Approval yang disetujui hanya dapat memulai satu sesi edit. Petugas B menentukan `expires_at` saat approval. Sesi berakhir ketika revisi dikirim, dibatalkan, atau waktu kedaluwarsa tercapai. Setelah sesi berakhir, petugas harus membuat permintaan approval baru.

`payload` JSON mempercepat MVP, tetapi field yang dipakai untuk filter petugas harus memiliki kolom terindeks. Keputusan JSON versus tabel normal harus diuji terhadap kebutuhan laporan dan perubahan format.

## 12. Interface dan Route Contract

MVP tidak membutuhkan API publik terpisah. Livewire menjadi boundary form publik.

Route konseptual:

- `GET /buat-proposal-kkprl` — mulai proposal.
- `GET /lanjutkan-proposal-kkprl` — verifikasi tiket + nomor HP.
- `GET /proposal-kkprl` — wizard setelah session access.
- `GET /proposal-kkprl/dokumen/{chapter}/{format}` — download satu bab terotorisasi.

Nama route final mengikuti konvensi route aplikasi. Nomor tiket tidak boleh menjadi satu-satunya credential pada route download.

Kontrak internal minimum:

- `startDraft(input) -> ProposalAccess`
- `resumeDraft(ticketNumber, phone) -> ProposalAccess`
- `saveDraft(proposalId, step, payload, attachments) -> DraftState`
- `validateForGeneration(proposalId) -> ValidationResult`
- `generateDocument(proposalId, chapter, format) -> ProposalDocument`
- `reviewProposal(proposalId, action) -> ReviewState`

Semua boundary menerima input yang sudah divalidasi dan mengembalikan hasil konsisten. Error validasi harus membedakan error field dari error akses tanpa membocorkan keberadaan proposal lain.

## 13. Dokumen Word dan PDF

Generator harus memakai sumber data bab yang sama untuk Word dan PDF.

MVP menggunakan export per bab. Sistem tidak wajib membuat satu file gabungan seluruh bab karena file gabungan dapat besar dan membutuhkan proses lebih berat. Export parsial menjadi cara utama pemohon dan petugas mengambil dokumen.

Persyaratan:

- Mapping field ke bagian template terdokumentasi.
- Setiap bab memiliki Word dan PDF sendiri.
- Export hanya membaca payload dan lampiran yang relevan dengan bab tersebut.
- Lampiran yang dipetakan ke bab wajib ikut masuk ke Word dan PDF bab tersebut, bukan hanya muncul sebagai daftar file.
- Lampiran disusun dalam urutan deterministik setelah bagian data yang merujuknya, dengan judul/caption dan nomor lampiran yang konsisten.
- Gambar dengan `placement = inline` dirender tepat setelah field atau blok teks acuannya; gambar tidak boleh hanya muncul di bagian akhir jika pengguna memilih posisi inline.
- Gambar tidak boleh terdistorsi; ukuran disesuaikan dengan lebar halaman, rasio aspek dipertahankan, dan gambar besar tidak boleh melewati batas halaman tanpa pengaturan layout.
- WebP diterima sebagai format upload; bila engine DOCX/PDF memerlukannya, generator menormalisasi salinan render menjadi PNG/JPEG tanpa mengubah file asli yang disimpan.
- Tabel harus memiliki header yang jelas, membungkus teks panjang, mengulang header saat melewati halaman, dan memakai orientasi landscape bila lebar tabel memerlukannya.
- Site plan, peta, foto, dan gambar pendukung harus memiliki caption, sumber atau keterangan bila tersedia, serta page break yang mencegah gambar terbelah secara buruk.
- Lampiran PDF harus ikut digabung sebagai halaman lampiran pada PDF dan sebagai halaman ter-render/representasi terkontrol pada Word; PDF tidak boleh hanya menjadi tautan publik.
- Upload harus memvalidasi MIME dan signature file, bukan hanya ekstensi nama file.
- Setiap bab menolak lebih dari 10 lampiran aktif atau total ukuran lampiran di atas 15 MB.
- Generator harus memberi error yang jelas jika tipe lampiran tidak dapat digabungkan secara aman.
- Export Bab 1–3 dapat dilakukan setelah bab masing-masing lengkap, tanpa menunggu bab lain.
- Export Bab 4/5 hanya tersedia jika bagian tersebut aktif dan lengkap.
- Bagian kondisional tidak muncul jika tidak dipilih.
- Dokumen menyimpan versi template.
- Generate ulang memakai data terbaru yang tersimpan.

Karena lampiran ikut digabung, generator wajib memiliki batas ukuran/proses, cache berdasarkan `snapshot_hash`, dan retry yang aman. Jika proses terlalu berat untuk request langsung, UI menampilkan status pembuatan dan menyediakan download segera setelah file selesai; pengalaman pengguna tetap berupa download Word/PDF langsung dari proposal.
- Snapshot hash memastikan Word dan PDF berasal dari data yang sama.
- Kegagalan render dicatat dan dapat diulang.

Route konseptual download:

- `GET /proposal-kkprl/dokumen/{chapter}/{format}` — download satu bab terotorisasi.

Setiap download tetap memeriksa session access pemohon atau permission petugas.

Dependensi library DOCX belum ada di `composer.json`. Pilihan library atau strategi template merge harus ditentukan sebelum task document generation dimulai.

## 14. Filament Review

Tambahkan resource di panel `Layanankkprl`, bukan panel baru.

Daftar proposal minimal menampilkan nomor tiket, nama pemohon, instansi/perusahaan, jenis kegiatan, wilayah administratif, status, progress, dan waktu update terakhir.

Halaman detail menampilkan ringkasan identitas, semua bagian proposal, lampiran privat dengan action terotorisasi, dokumen Word/PDF, serta riwayat status atau review jika status review sudah ditetapkan.

Petugas tidak boleh melihat atau mengubah data sensitif melebihi permission yang dimiliki.

## 15. Testing Strategy

### Unit test

- Normalisasi nomor HP.
- Hash/HMAC lookup.
- Aturan kondisi Bag 4 dan Bag 5.
- Perhitungan progress.
- Validasi final versus validasi draft.
- Mapping field ke format dokumen.
- Sanitasi nama file dan aturan upload.

### Feature test

- Membuat draft menghasilkan tiket unik.
- Pasangan tiket + nomor HP benar dapat melanjutkan.
- Pasangan salah ditolak secara generik.
- Rate limit akses diterapkan.
- Draft tersimpan lintas request/session.
- Bag 4 tidak tersimpan/ter-render jika reklamasi false.
- Bag 5 mengikuti pilihan lahan dan izin.
- File hanya dapat diunduh oleh pemohon terverifikasi atau petugas berizin.
- Generate Word/PDF menggunakan snapshot yang sama.

### Livewire/browser test

- Wizard dapat digunakan pada viewport HP.
- Navigasi antarbagian tidak menghapus data.
- Autosave menampilkan waktu simpan terakhir.
- Upload pada jaringan lambat memiliki state loading/error jelas.
- Pengguna dapat keluar lalu melanjutkan draft.

### Verifikasi manual

- Android Chrome pada koneksi lambat.
- File besar, file salah tipe, dan upload terputus.
- Dua proposal dengan nomor HP sama.
- Akses silang memakai nomor tiket proposal lain.
- Semua kombinasi kondisional utama saat render.

Perintah dasar proyek saat implementasi:

```bash
rtk php artisan test
rtk php artisan test --filter=KkprlProposal
rtk npm run build
```

## 16. Batasan Kerja

### Always

- Perbarui spesifikasi sebelum mengubah keputusan data atau alur.
- Validasi semua input publik di boundary Livewire.
- Gunakan private storage untuk file sensitif.
- Tulis test untuk aturan kondisional dan akses.
- Jalankan focused test setelah setiap vertical slice.
- Tinjau output Word/PDF dengan contoh data yang sudah disamarkan.

### Ask first

- Menambah dependency Composer untuk DOCX.
- Mengubah tabel `clients` atau workflow booking lama.
- Menambah status review baru.
- Mengubah aturan validitas PKKPRL/KKRL.
- Membuka endpoint API publik.
- Mengubah policy atau permission Filament.
- Menetapkan field yang wajib secara hukum/regulasi.

### Never

- Menyimpan nomor HP atau token akses sebagai plaintext jika pola proteksi aplikasi mendukung enkripsi/hash.
- Menaruh file proposal di public storage.
- Menganggap nomor tiket saja cukup untuk otorisasi.
- Menghapus draft tanpa aturan retensi dan persetujuan.
- Menandai proposal lengkap hanya karena file berhasil dibuat.
- Mengisi kekosongan regulasi dengan asumsi teknis tanpa konfirmasi pemilik proses.

## 17. Success Criteria MVP

MVP berhasil jika:

1. Pemohon dapat membuat draft tanpa login.
2. Sistem menghasilkan nomor tiket dan menampilkan cara menyimpannya.
3. Pemohon dapat melanjutkan dengan nomor tiket + nomor HP.
4. Pemohon dapat mengisi bertahap tanpa kehilangan data.
5. Bag 1–3 tampil untuk semua proposal.
6. Bag 4 dan Bag 5 tampil sesuai kondisi yang dipilih.
7. Koordinat manual dan upload dokumen berjalan di HP.
8. Validasi final mencegah dokumen tidak lengkap berdasarkan kondisi proposal.
9. Bab 1–3 wajib lengkap dan dapat diekspor masing-masing ke Word/PDF.
10. Bab 4/5 dapat diekspor masing-masing jika kondisi aktif dan data lengkap.
11. Pemohon dapat mengirim proposal dengan status `submitted` untuk review petugas.
12. Petugas dapat meninjau proposal dan file per bab dari Filament.
13. UI dan dokumen menjelaskan bahwa aplikasi bukan sistem perizinan utama dan tidak menggantikan e-Sea/OSS.
14. Akses silang, file publik, dan percobaan akses berulang ditolak.
15. Test akses, draft, kondisi form, upload, dan generator per bab lulus.


## 18. Integrasi AI Agent (Gemini Flash)

Fitur pengisian form statis untuk Bab 2, 3, 4, dan 5 digantikan dengan **Blended UI (Chatbot + Preview Card)**.
- **AI Engine:** Gemini Flash 1.5/2.0 via API. Dipilih karena biaya murah, *context window* besar, dan *native vision*.
- **Blended UI:** Layar dibagi menjadi porsi Obrolan (Chat) dan Kartu Pratinjau (Preview). AI mewawancarai pengguna, mengekstrak jawaban menjadi JSON, lalu meng-update Kartu Pratinjau.
- **Konfirmasi Manual (Anti-Halusinasi):** AI tidak menyimpan data secara diam-diam. Pengguna harus mengklik "Konfirmasi & Simpan" pada Kartu Pratinjau agar data masuk ke `payload` di tabel `kkprl_proposals`.
- **Klasifikasi Lampiran:** Saat pengguna upload file, sistem memilah:
  - *Sensitive* (KTP, NIB): Disimpan via `KkprlProposalAttachmentService` murni, AI tidak memiliki akses.
  - *Non-Sensitive* (Foto Ekosistem, Peta): Dikirim ke API Gemini Vision bersama prompt untuk mendeskripsikan kondisi lokasi, hasilnya ditambahkan ke Kartu Pratinjau.

## 19. Pertanyaan Terbuka

Pertanyaan berikut harus dijawab sebelum implementasi bagian terkait:

1. Format koordinat: desimal, derajat-menit-detik, atau keduanya?
2. Satuan resmi luas perairan, luas reklamasi, volume material, dan kedalaman?
3. Strategi generator Word: template merge, HTML-to-DOCX, atau library DOCX khusus?
4. Berapa lama draft dan file disimpan?
5. Apakah nomor KTP wajib untuk semua pemohon atau kategori tertentu?
6. Apakah notifikasi email/WhatsApp diperlukan setelah draft dibuat atau dokumen selesai?

## 20. Referensi

- `docs/template/SURAT PERMOHONAN PERIZINAN NON BERUSAHA_KHUSUS SUBMIT MELALUI E-SEA KKP.docx`
- `docs/template/Bag 1. Proposal KKPRL_RENCANA BANGUNAN DAN INSTALASI LAUT.docx`
- `docs/template/Bag 2. Proposal KKPRL_INFORMASI PEMANFAATAN RUANG LAUT.docx`
- `docs/template/Bag 3. Proposal KKPRL_KONDISI TERKINI PERAIRAN DAN SEKITARNYA.docx`
- `docs/template/Bag 4. Proposal KKPRL_PERSYARATAN REKLAMASI.docx`
- `docs/template/Bag 5. Proposal KKPRL_PERIZINAN LAINNYA.docx`
- `app/Livewire/BookingWizard.php`
- `app/Filament/Layanankkprl/Resources/Clients/`
- `app/Models/Client.php`
- `docs/upgrade-prd.md`
- `docs/servicekkprl-mobile-development-plan.md`

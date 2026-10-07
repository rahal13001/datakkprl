# Spesifikasi: Pembuat Proposal KKPRL

> Ukuran peta Bab 1, 8 Oktober 2026: Polygon menampilkan perkiraan luas hektar dan Line menampilkan jumlah panjang segmen kilometer, masing-masing dua desimal. Warning membandingkan hasil dengan dimensi isian pada satuan yang cocok dan tidak mengubah isian atau menghalangi workflow. Nilai computed hanya informasi frontend dan bukan hasil survei. Lihat [spesifikasi ukuran peta](kkprl-map-measurement-spec.md), [validasi](kkprl-map-measurement-validation.md), dan [ADR-028](decisions/ADR-028-informational-map-dimensions.md).

> Perbaikan Bab 1, 8 Oktober 2026: autosave memakai state UI stabil tanpa menyimpan scaffold, katalog 36 isian ditampilkan beserta daftar yang kosong, dan satuan kedalaman/datum kini tersedia. Editor rencana tapak baru kosong; petunjuk berada di luar konten dan pengiriman dilakukan saat blur/action wizard. Format payload flat legacy dan hash dokumen dipertahankan ketika tidak ada edit isi. Lihat [spesifikasi Bab 1](kkprl-chapter-one-input-spec.md), [validasi aktual](kkprl-chapter-one-input-validation.md), serta [ADR-027](decisions/ADR-027-chapter-one-input-state-and-completion.md). Progress keseluruhan mencakup semua bab aktif dan bukan ukuran kepastian regulasi.

> Akses pemohon pada kode ini menggunakan akun email/password dan approval petugas. Lihat [spesifikasi akun](kkprl-applicant-account-spec.md), ADR-024–026 dan [validasi aktual](kkprl-applicant-account-validation.md). ADR-001 tetap menjadi riwayat keputusan tiket/HP yang telah digantikan. Migration/seeder belum dijalankan pada database pengguna.

## Status Dokumen

- Status: Disetujui (Updated with AI Integration Plan)
- Tanggal: 2026-08-24
- Produk: Laravel + Livewire + Filament
- Sumber format: `docs/template/*.docx`
- Pengguna utama: pengguna jasa dan pelaku usaha kecil
- Panel petugas: Filament `Layanankkprl`

Dokumen ini menjadi sumber kebenaran untuk MVP pembuat proposal KKPRL. Perubahan kebutuhan harus memperbarui dokumen ini sebelum kode diubah.

## 1. Tujuan

Menyediakan wizard formulir publik yang ringan di HP untuk membantu pemohon membuat proposal KKPRL secara bertahap, menyimpan draft, melanjutkan pengisian melalui akun yang telah disetujui, lalu mengunduh proposal dalam format Word dan PDF.

Petugas tetap dapat membuka, memeriksa, dan meninjau proposal dari panel Filament.

Fitur ini adalah sistem pendukung penyusunan dan review dokumen. Fitur ini bukan sistem perizinan utama dan tidak menggantikan e-Sea, OSS, atau kanal resmi lain untuk pengajuan dan penerbitan perizinan.

## 2. Capability Map

| Module ID | Tanggung jawab | Bergantung pada |
|---|---|---|
| `proposal-access` | Registrasi/login email-password, approval, kepemilikan, wadah dokumen generated, pemilihan proposal | — |
| `proposal-draft` | Membuat draft, autosave, progress, status, pemulihan data | `proposal-access` |
| `proposal-form` | Form Bag 1–5, validasi, kondisi bagian, upload lampiran | `proposal-draft` |
| `proposal-document` | Render proposal ke Word/PDF berdasarkan snapshot data | `proposal-draft`, `proposal-form` |
| `proposal-review` | Daftar, detail, filter, dan peninjauan petugas di Filament | `proposal-draft`, `proposal-document` |
| `proposal-ai-chat` | Asisten AI (Gemini Flash) per bab untuk tanya jawab, ekstraksi payload JSON, baca lampiran non-sensitif | `proposal-draft` |

Urutan build: `proposal-access` → `proposal-draft` → `proposal-form` (Bab 1) & `proposal-ai-chat` (Bab 2-5) → `proposal-document` → `proposal-review`.

## 3. Keputusan Produk yang Sudah Disetujui

1. Pemohon mendaftar dengan email dan password; akun baru pending tanpa draft atau panggilan AI.
2. Pending hanya dapat melihat/mengunduh dokumen generated miliknya. Builder memerlukan approval petugas dan kepemilikan.
3. Pemulihan password dilakukan petugas berpermission khusus; tidak ada reset mandiri email/OTP/WhatsApp.
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
5. Nomor HP tetap data kontak proposal; bukan kredensial atau dasar penetapan owner.
6. Guard pemohon terpisah dari petugas; versi sesi fresh menolak sesi lama setelah reset.
7. File hasil generate dan lampiran diperlakukan sebagai data privat.
8. Template Word yang ada menjadi referensi format, bukan otomatis dianggap siap dipakai sebagai template merge.
9. Tipe lampiran yang didukung MVP dibatasi pada PDF, JPG/JPEG, PNG, dan WebP.
10. Setiap bab memiliki kuota maksimal 10 lampiran dengan total ukuran gabungan maksimal 15 MB; validator teknis memakai 15 MiB (15.728.640 byte / 15.360 KB).

Asumsi nomor 5–8 harus diverifikasi saat desain teknis dan implementasi.

## 5. Pengguna dan Hak Akses

### 5.1 Pemohon publik

Pemohon pending hanya melihat/mengunduh dokumen generated sendiri yang lolos pemeriksaan integritas, atau empty state singkat. Tidak ada wizard, draft, chat, upload, peta, progres atau generate. Pemohon approved dapat membuat dan memilih proposal miliknya, menyimpan draft yang belum dikunci, mengelola lampiran, menggunakan AI/GMRT dan mengunduh Word/PDF. Approval tidak melewati lock, revisi, kuota atau validasi.

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

1. Daftar melalui `/daftar-proposal-kkprl` dengan email, password dan konfirmasi password.
2. Login melalui `/masuk-proposal-kkprl`. Akun baru membuka `/akun-proposal-kkprl` tanpa draft otomatis.
3. Sebelum approval, wadah hanya memuat dokumen generated milik akun atau **Belum ada dokumen.**
4. Petugas berpermission menyetujui akses builder. Pemohon memilih **Buat proposal baru**.
5. Backend membuat draft dan tiket internal serta menetapkan owner dari guard, bukan input.
6. Pemohon melengkapi kontak dan data proposal sesuai template dalam wizard.

### 6.2 Melanjutkan draft

1. Login akun dan buka **Proposal milik Anda**.
2. Pilih proposal/revisi sendiri; selection session hanya memilih proposal, bukan memberikan kewenangan.
3. Akun harus approved, owner cocok, dan versi sesi fresh pada mount/hydrate/action/service.
4. Proposal terkunci tetap terkunci. Revisi baru dibuat melalui workflow petugas dan mewarisi owner root.
5. URL lama start/resume mengarahkan ke login atau wadah akun. Pasangan tiket/HP dan sesi lama tidak memberi grant.
6. Lupa password: hubungi petugas berpermission reset, yang memasukkan password baru dan konfirmasi. Sampaikan secara manual melalui kanal privat organisasi.

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
- Bentuk lokasi: titik, garis, atau polygon.
- Daftar koordinat WGS84, diisi melalui peta atau DD/DMS. Nilai desimal tiap titik disimpan pada `coordinates_raw`, dengan teks `coordinates` untuk dokumen. Latitude harus -90 sampai 90; longitude -180 sampai 180. Garis membutuhkan minimal dua titik dan polygon tiga titik.
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
- Rincian wajib izin yang ada: jenis, tahun perolehan dan status. Nomor, instansi penerbit, masa berlaku, dokumen dan catatan opsional.
- Rincian wajib lahan: status, jenis bukti dan tahun perolehan; dokumen serta catatan opsional.
- Bab 5 menyediakan pilihan sudah/belum memiliki izin pendukung. Pilihan masuk checkpoint dahulu, lalu memperbarui has_existing_permits Bab 1 saat Konfirmasi & Simpan Draft. Jika belum memiliki izin, bagian perizinan dilewati; jika tidak ada kebutuhan lahan juga, Bab 5 tidak menjadi syarat kelengkapan.
- Data izin yang tidak aktif tetap tersimpan untuk koreksi, tetapi tidak masuk snapshot/ekspor. Rincian opsional yang diisi tetap diekspor. Lihat ADR-022.
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
6. Pemohon login akun dan memilih revisi miliknya pada wadah akun. Owner revisi diwariskan dari root; proposal legacy tetap tanpa owner sampai penetapan manual terverifikasi dan diaudit.
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

Akun email/password memakai model/provider/guard pemohon terpisah. Password di-hash; registrasi menggunakan input allowlist dan throttle. Approval `approved_at` berbeda dari `User.status`. Sesi pemohon mengikat `session_version`, yang dinaikkan saat reset oleh petugas; setiap boundary mengecek akun fresh.

- Pending hanya mengakses dokumen generated sendiri; data proposal dan lampiran mentah tetap tertutup.
- Builder memeriksa approval dan owner pada request langsung, hydrate Livewire, action dan service.
- Persistence setelah AI/GMRT/render memeriksa ulang akun, versi, approval, owner, lock dan checkpoint; akun dikunci saat transaksi penulisan.
- Upload sementara KKPRL menggunakan route signed khusus yang terikat applicant/proposal/versi sesi. Penerimaan file memeriksa izin kembali, tanpa mengubah upload sistem lain.
- Private storage, MIME/signature, nama file aman, quota dan pemeriksaan snapshot/manifest tetap berlaku.
- Permission daftar/detail, approval, reset dan penetapan owner terpisah; hanya role yang disepakati menerima permission dari seeder khusus. Approval edit petugas tetap workflow tersendiri.
- Audit append-only mencatat actor/target/waktu/tindakan; tidak mencatat password atau hash. Alasan/reference kepemilikan dienkripsi.
- Assignment legacy memerlukan bukti terverifikasi, alasan dan referensi manual. Tidak ada klaim berdasarkan tiket, email atau HP semata.

Security review khusus wajib dilakukan sebelum fitur dibuka publik.

## 11. Model Data Awal yang Diusulkan

Model final harus mengikuti pola migrasi dan encryption yang sudah ada. Rancangan awal:

### `kkprl_proposals`

- `id`.
- `applicant_id`, FK nullable untuk legacy, ditetapkan backend; revisi mengikuti owner root.
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

- `GET /buat-proposal-kkprl` — redirect login/wadah akun; tidak membuat draft.
- `GET /lanjutkan-proposal-kkprl` — redirect login/wadah akun (legacy fail-closed).
- `GET /masuk-proposal-kkprl`, `/daftar-proposal-kkprl`, `/akun-proposal-kkprl` — identitas pemohon dan dokumen generated.
- `POST /proposal-kkprl/uploads` — penerimaan temporary upload signed yang terikat akun, proposal dan versi sesi.
- `GET /proposal-kkprl` — wizard setelah akun approved memilih proposal sendiri.
- `GET /proposal-kkprl/documents/{document}` — download dokumen generated terotorisasi.
- `GET /proposal-kkprl/attachments/{attachment}` — lampiran mentah; hanya pemohon approved dalam scope atau petugas berpermission.

Nama route final mengikuti konvensi route aplikasi. Nomor tiket tidak boleh menjadi satu-satunya credential pada route download.

Kontrak internal minimum:

- `startDraft(input) -> ProposalAccess`
- `resumeDraft(ticketNumber, phone)` — compatibility stub menolak semua kredensial legacy.
- `KkprlApplicantAuthService` — register/login/logout dan pemeriksaan versi sesi fresh.
- `KkprlApplicantAdministrationService` — approval/reset/owner assignment dengan permission/audit.
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

- `GET /proposal-kkprl/documents/{document}` — download dokumen generated terotorisasi.
- `GET /proposal-kkprl/attachments/{attachment}` — lampiran mentah; hanya pemohon approved dalam scope atau petugas berpermission.

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
- Login akun approved dapat memilih proposal sendiri; tiket/HP lama selalu ditolak.
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
- Akses silang akun melalui ID proposal/dokumen dan sesi/URL tiket lama ditolak.
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

1. Pemohon mendaftar/login email-password; hanya akun approved dapat membuat draft miliknya. Pending hanya membuka dokumen generated sendiri atau empty state.
2. Registrasi hanya membuat akun pending; draft/tiket dibuat setelah approval dan tindakan Buat proposal baru.
3. Pemohon dapat melanjutkan melalui akun approved dan hanya memilih proposal/revisi miliknya.
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
- **AI Engine:** Gemini `gemini-3.8-flash` via API, memakai kemampuan percakapan dan pemahaman gambar native melalui HTTP Laravel.
- **Blended UI:** Layar dibagi menjadi porsi Obrolan (Chat) dan Kartu Pratinjau (Preview). AI mewawancarai pengguna, mengekstrak jawaban menjadi JSON, lalu meng-update Kartu Pratinjau.
- **Konfirmasi Manual (Anti-Halusinasi):** AI tidak menyimpan data secara diam-diam. Pengguna harus mengklik "Konfirmasi & Simpan" pada Kartu Pratinjau agar data masuk ke `payload` di tabel `kkprl_proposals`.
- **Dua cara koreksi:** Pengguna dapat meminta revisi melalui percakapan AI atau mengedit langsung field pada kartu pratinjau Bab 2–5. Label dan key mengikuti katalog field template; jadwal reklamasi dapat diedit per baris.
- **Pratinjau bertahap:** Pratinjau boleh belum lengkap. Koreksi disimpan sebagai konteks sesi chat agar dapat dilanjutkan, tetapi belum menjadi payload proposal sebelum pengguna menekan **Konfirmasi & Simpan Draft**. Pengguna dapat mengosongkan kembali field yang perlu diperiksa.
- **Konteks koreksi:** Permintaan AI berikutnya menggunakan pratinjau terbaru, termasuk koreksi manual. AI hanya mengubah field yang dibahas dan tidak mengarang fakta yang belum diberikan atau dikonfirmasi.
- **Percakapan ramah pengguna:** Blok JSON diproses sebagai data internal dan tidak ditampilkan dalam chat, termasuk saat respons terpotong atau riwayat lama dibuka kembali. Saat draft siap, asisten mengarahkan pengguna membaca Pratinjau Draft, mengoreksi melalui chat atau editor, lalu menekan **Konfirmasi & Simpan Draft**. Pertanyaan lanjutan tetap tampil jika informasi perlu dilengkapi. Respons yang tidak valid tidak menimpa pratinjau sebelumnya dan menampilkan arahan mencoba ulang.
- **Penyimpanan konfirmasi:** Konfirmasi memakai service draft yang sama dengan wizard, mempertahankan data bab lain, memperbarui progress dan waktu simpan, serta mengikuti pemeriksaan session access, bab aktif, dan penguncian proposal. Export tetap memerlukan kelengkapan bab.
- **Klasifikasi Lampiran:** Saat pengguna upload file, sistem memilah:
  - *Sensitive* (KTP, NIB): Disimpan via `KkprlProposalAttachmentService` murni, AI tidak memiliki akses.
  - *Non-Sensitive* (Foto Ekosistem, Peta): Dikirim ke API Gemini Vision bersama prompt untuk mendeskripsikan kondisi lokasi, hasilnya ditambahkan ke Kartu Pratinjau.
- **Bukti gambar Bab 3:** Pengguna memilih mangrove, lamun, karang, arus, gelombang, pasang surut, batimetri atau profil dasar laut. Panduan menampilkan koordinat Bab 1, tautan sumber dan cara mengambil screenshot dengan legenda/lokasi/periode. Foto sendiri dan sumber lain didukung. Bukti bersifat opsional; chat tetap tersedia.
- **Sumber bawaan batimetri:** Garmin Interactive Map memakai tautan viewer yang diberikan pengguna. Pemilihan batimetri mengisi sumber, URL dan kredit Garmin/Navionics otomatis. Panduan screenshot meminta lokasi sesuai Bab 1, angka kedalaman/kontur, satuan, skala dan kredit. Peta merupakan informasi sekunder; izin penggunaan mengikuti penyedia. GEBCO tetap dapat dipilih manual dan tetap menjadi sumber bawaan profil dasar laut. Metadata lampiran lama dipertahankan.
- **Data tabel pasang surut:** Pada topik `tide`, pengguna dapat mengunggah gambar, CSV/XLS/XLSX, atau keduanya. Tabel terbaru diutamakan saat analisis, termasuk bila tombol gambar pasut dipakai. Form unggah terlihat jelas dengan tombol pilih file, nama file, status dan panduan. CSV UTF-8 menerima koma/titik koma/tab; Excel memakai worksheet pertama, dengan judul kolom pada baris pertama. Pengguna memeriksa mapping tanggal/waktu, tinggi, jam terpisah, format tanggal, satuan m/cm/mm, zona waktu dan acuan tinggi.
- **CSV SRGI BIG asli:** Informasi instansi, stasiun, koordinat dan datum sebelum header `timestamp_utc,timestamp_local,predicted_height_m` dikenali tanpa pengguna mengedit file. Form memilih UTC dan meter, serta datum dari file. Waktu lokal dengan penanda WIB/WITA/WIT/UTC diterima jika sesuai zona pilihan. Metadata stasiun disimpan sebagai konteks sumber dan tidak menggantikan koordinat proposal. Tinggi prediksi diberi keterangan sebagai prediksi, bukan pengukuran langsung; acuan MSL dari file tidak disimpulkan melalui rata-rata sampel. CSV umum tanpa header tetap ditolak.
- **Pemilihan zona CSV dengan dua timestamp:** Pilihan UTC/lokal dan kolom `timestamp_utc`/`timestamp_local` saling menyesuaikan otomatis pada perubahan form dan diperiksa kembali sebelum simpan. Kedua kolom berisi tanggal/jam lengkap; opsi jam terpisah dinonaktifkan. Zona yang tidak sesuai kolom lokal file tidak tersedia. Mapping tanggal/jam pada CSV umum tetap manual.
- **Perhitungan pasut tanpa AI:** Tombol **Hitung data pasut** menghitung statistik serta amplitudo efektif delapan komponen melalui kuadrat terkecil/QR, lalu membuat narasi awal di pratinjau. Estimasi Formzahl `(K1+O1)/(M2+S2)` dan tipe hanya tersedia jika rekaman minimal 28 hari/200 sampel, cakupan minimal 90%, jeda maksimal 3 jam, model stabil dan R² pasut setelah pemisahan tren minimal 0,90. Hasil yang tidak memadai tetap menampilkan statistik, narasi dan alasannya. Kebijakan mutu ini bukan standar otoritas. Seluruh sampel digunakan tanpa interpolasi. Hasil merupakan estimasi periode, bukan konstanta astronomis stasiun; P1/K1 dan K2/S2 belum dipisahkan, tanpa koreksi nodal/inferensi/interval kepercayaan. Rata-rata sampel bukan MSL dan ekstrem periode bukan HHWL/LLWL/HAT/LAT. Baris rusak, duplikat waktu, rumus dan format palsu tetap ditolak. Keputusan: [ADR-019](decisions/ADR-019-backend-tidal-harmonics.md).
- **Grafik pasut dari backend:** Tombol **Buat grafik pasut** membuat PNG dari seluruh sampel tabel terbaru melalui PHP GD, tanpa layanan AI dan tanpa mengganti narasi manual. Sumbu waktu memakai instan sebenarnya, tinggi dikonversi ke meter, datum dan zona waktu dicantumkan; prediksi BIG dibedakan dari pengukuran. Garis hanya penghubung visual, tanpa pemulusan atau pengisian celah. Jeda lebih dari 1,5 kali median interval diputus dan dilaporkan (kebijakan visual aplikasi, bukan standar resmi). Sumber/kredit, periode, nama/hash file dan metadata stasiun menyertai gambar. Grafik privat inline `tide` ikut snapshot/Word/PDF dan dapat dilihat/diunduh. Hasil identik tidak membuat lampiran tambahan; gagal/kuota/perubahan data atau checkpoint mempertahankan gambar lama dan koreksi narasi. Grafik memakai satu slot kuota Bab 3. Keputusan: [ADR-023](decisions/ADR-023-grafik-pasut-backend.md).
- **Narasi pasut dan AI opsional:** Hasil, keterbatasan dan sumber tersedia tanpa Gemini dan tetap bisa dikonfirmasi menjadi draft. Tombol **Rapikan narasi dengan AI** memakai ringkasan backend; AI tidak menghitung ulang angka atau tipe. Jika pratinjau kosong, narasi awal dibuat sebelum request. Timeout mempertahankan narasi dan hasil. Menghitung ulang meminta konfirmasi UI bila akan mengganti teks yang terisi. Metadata harmonik berversi mengikuti snapshot/Word/PDF; cache tetap memerlukan checksum, akses, lock, koordinat, tabel terbaru dan checkpoint yang sesuai. Tidak ada fallback diam-diam ke gambar jika tabel bermasalah.
- **Lampiran data pasut:** Privat sebagai inline `ai_tide_data` pada `tide`, maksimal 5 MiB, 50.000 baris data (di luar judul kolom), 20 kolom, area Excel 200.000 sel dan isi ZIP 32 MiB. Mendukung seluruh data per menit selama 30/31 hari (43.201/44.641 sampel termasuk kedua batas waktu), tanpa pemotongan atau pengurangan frekuensi. Excel dibaca bertahap untuk membatasi objek sel di memori. Kuota bersama Bab 3 tetap 10 file/15 MiB. Mapping, ringkasan perhitungan, sumber serta koordinat disimpan dalam metadata yang ikut snapshot/revisi. Word/PDF menampilkan ringkasan data dan sumber. Memakai PhpSpreadsheet yang sudah tersedia; tidak ada dependency atau migrasi baru. Konfirmasi manual, pengecekan akses/lock/checksum/koordinat/checkpoint dan kuota Gemini tetap berlaku. Keputusan: [ADR-018](decisions/ADR-018-tidal-table-evidence.md).
- **Provenance:** Gambar disimpan privat sebagai lampiran inline `ai_evidence`, lengkap dengan sumber, URL, periode, tanggal akses, atribusi, keterangan dan koordinat saat unggah. Metadata dipertahankan dalam snapshot/manifest serta revisi; caption/sitasi ikut Word/PDF. JPG/PNG/WebP maksimal 5 MiB memakai kuota bersama 10 file/15 MiB per bab.
- **Penampang langsung Bab 3:** Pada topik Profil dasar laut, pengguna memilih dua titik A/B pada peta yang menampilkan referensi koordinat Bab 1, atau mengisi koordinat desimal. Backend mengambil sampel GMRT ProfileServer melalui HTTPS, membuat grafik PNG dan narasi awal tanpa Gemini, lalu menyimpan grafik sebagai bukti privat inline dengan sumber, lisensi CC BY 4.0, waktu akses, endpoint, sampel dan checksum respons. AI opsional memakai grafik/ringkasan backend; ringkasan angka dan keterbatasan tetap melekat. Nilai elevasi positif/negatif dipertahankan; celah data tidak diinterpolasi. Grafik grid sekunder bukan survei, bukan bukti sedimen atau datum pasut, dan jumlah sampel bukan resolusi grid. Lintasan lokal didukung 10 m–100 km. Akses, kunci, koordinat dan checkpoint diperiksa kembali setelah request. Kegagalan mempertahankan hasil lama. Bersihkan lintasan memulai lagi dari A, tanpa menghapus grafik. Membuat ulang saat pratinjau terisi meminta konfirmasi penggantian narasi. Upload grafik dari sumber lain tetap tersedia. Lihat ADR-020.
- **Analisis terbatas:** Satu permintaan membaca satu gambar dan hanya memperbarui field terkait pada pratinjau. AI tidak menebak angka, spesies, kesehatan karang, sedimen, penampang terukur atau ketiadaan habitat. Gambar tidak jelas dapat memicu klarifikasi. Respons invalid, kegagalan API atau perubahan akses/lock/bukti/koordinat/checkpoint mempertahankan koreksi sebelumnya.
- **Kegagalan dan kuota AI:** Chat/gambar membedakan kuota harian, batas sementara, konfigurasi, koneksi dan kegagalan provider. HTTP 429 menampilkan waktu tunggu dan menahan permintaan ulang pada cache persisten bersama mengikuti informasi provider. Gambar, narasi dan jawaban tetap tersedia; pengguna dapat mengedit pratinjau. Tidak ada perpindahan otomatis ke layanan berbayar. Pengelola dapat menjalankan `php artisan kkprl:ai-reset-cooldown` setelah peningkatan kuota untuk menghapus penundaan lokal; command tidak mengubah kuota Google. Key/model memakai `services.gemini` tanpa dependency baru.
- **Kompatibilitas shared hosting:** Tidak membutuhkan instalasi paket atau program baru. Pengguna mengakses sumber secara manual; aplikasi tidak mengambil URL sumber atau melakukan scraping. Akses gratis bukan jaminan izin penggunaan screenshot komersial; panduan mencantumkan batasan khusus Atlas/BIG serta alternatif. Keputusan dan validasi: [ADR-017](decisions/ADR-017-chapter-three-image-evidence.md), [laporan pengujian](kkprl-chapter-three-evidence-validation.md).

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


### Batimetri GMRT langsung pada Bab 3 (ADR-021)

Saat memilih Batimetri, GMRT menjadi sumber default. Tombol **Buat peta batimetri & narasi awal** memakai seluruh koordinat Bab 1 dan tambahan area 0,5/1/2/5 km (default 1 km), tanpa screenshot atau panggilan Gemini. Grid ASCII diperiksa backend; PHP GD membentuk raster warna, kontur antar pusat sel, legenda elevasi meter, koordinat WGS84, skala perkiraan dan penanda/bentuk Bab 1. NoData tetap kosong. Garmin, GEBCO dan unggah manual tetap tersedia.

Ringkasan menyatakan rentang elevasi **seluruh area peta**, jumlah sel dan jarak grid keluaran, bukan kedalaman titik usaha atau ketelitian asli. Datum vertikal, periode survei dan resolusi sumber asli belum diketahui. Angka positif tidak diperlakukan sebagai kedalaman dan peta tidak membuktikan jenis sedimen.

PNG disimpan privat dengan sumber, kredit CC BY 4.0, identitas koordinat/bentuk, ringkasan, SHA-256 serta arsip grid gzip/base64 pada source_metadata. AI opsional hanya mendapat gambar dan ringkasan; arsip grid tidak dikirim. Ringkasan backend dan lisensi tetap dilampirkan setelah AI menarasikan. Narasi masuk pratinjau dahulu; pengguna melakukan Konfirmasi & Simpan Draft. Peta/sumber ikut snapshot dan ekspor Word/PDF. Koneksi gagal, perubahan lokasi, akses/kunci, checkpoint tab lain dan kuota tidak menimpa bukti sebelumnya. Semua koordinat harus valid; area dibatasi 50 km per sisi, 65.536 sel dan 4 MiB respons.

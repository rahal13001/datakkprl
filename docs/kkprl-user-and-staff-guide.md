# Panduan Pengguna dan Petugas — Proposal KKPRL

Dokumen ini menjelaskan penggunaan sistem pendukung penyusunan dan review proposal KKPRL. Sistem ini bukan OSS, e-Sea, sistem perizinan utama, penerbit izin, atau sumber keputusan hukum/administratif.

## Pemohon

### Membuat dan melanjutkan draft

1. Daftar di `/daftar-proposal-kkprl` dengan email dan password minimal 12 karakter serta konfirmasi. Pendaftaran tidak membutuhkan tiket, HP atau identitas lain, dan tidak membuat draft.
2. Login di `/masuk-proposal-kkprl`. Wadah `/akun-proposal-kkprl` hanya menampilkan dokumen generated sendiri atau **Belum ada dokumen.** selama belum approved.
3. Setelah petugas menyetujui builder, pilih **Buat proposal baru**, lalu isi Bab 1–5 yang relevan. Data kontak mengikuti template proposal.
4. Untuk melanjutkan, login dan pilih proposal/revisi pada **Proposal milik Anda**. Tiket hanya identitas internal; tiket/HP tidak lagi memberikan akses.
5. Jika selection berakhir karena idle, kembali ke wadah akun dan pilih proposal. Jika approval dicabut, hanya wadah dokumen generated yang tersedia.
6. Lupa password: hubungi petugas. Petugas berpermission memasukkan password baru dan konfirmasi; penyampaian berlangsung manual melalui kanal privat organisasi. Semua sesi lama pemohon berakhir; login memakai password baru. Tidak ada reset mandiri email, OTP atau WhatsApp.

### Bab dan validasi

- Bab 1–3 selalu tersedia dan wajib lengkap saat dikirim.
- Bab 4 hanya muncul bila reklamasi dipilih.
- Bab 5 hanya menampilkan subbagian lahan darat dan/atau perizinan yang dipilih.
- Gunakan keterangan eksplisit **Tidak berlaku** bila field yang tampil memang tidak berlaku.
- Koordinat diisi manual sesuai format dan rentang yang diminta.

Bab yang belum lengkap tidak dapat diekspor dan proposal tidak dapat dikirim sebagai `submitted`.

### Mengisi Bab 1

Bagian **Kelengkapan Bab 1** menampilkan jumlah dari 36 isian wajib. Buka **Belum diisi** untuk melihat isian yang tersisa. Semua isian katalog wajib diisi, termasuk satuan kedalaman/datum, narasi rencana tapak dan tiga pilihan kondisi. Angka 0 dan pilihan Tidak dihitung; uraian yang tidak relevan dapat diisi **Tidak berlaku**. Cantumkan datum sesuai sumber atau nyatakan belum diketahui. Koordinat harus valid: minimal satu titik untuk Point, dua untuk Line, tiga untuk Polygon, latitude −90 sampai 90 dan longitude −180 sampai 180.

Bab 1 selesai tidak membuat progress bar keseluruhan menjadi 100%; bar tersebut mencakup semua bab aktif. Daftar isian wajib lengkap tersedia di [peta field](kkprl-template-field-map.md#kelengkapan-bab-1--8-oktober-2026). Progress bukan penilaian kelengkapan regulasi atau kebenaran ilmiah data.

Isian biasa disimpan ketika Anda keluar dari field. Narasi rencana tapak dimulai kosong untuk draft baru. Jelaskan bangunan, ukuran, letak, akses dan hubungan antarfasilitas sesuai gambar dan informasi yang sudah diketahui. Petunjuk editor bukan isi dokumen. Narasi disimpan saat keluar editor atau memakai action wizard; tunggu konfirmasi simpan sebelum menutup halaman. **Simpan Draft** tetap tersedia.

Peta menampilkan **Perkiraan luas peta** dalam hektar untuk Polygon atau **Perkiraan panjang peta** dalam kilometer untuk Line, masing-masing dua desimal. Input DD/DMS, klik peta dan drag marker langsung memperbarui hasil. Panjang menjumlahkan seluruh segmen. Point tidak mempunyai luas/panjang. Koordinat belum lengkap atau geometri tidak valid menampilkan petunjuk, bukan hasil lama.

Isi **Dimensi (Luas / Panjang)** dengan satuan Hektar (Ha) untuk Polygon atau Kilometer (Km) untuk Line. Jika isian berbeda dari ukuran peta setelah pembulatan dua desimal, peringatan muncul di bawah peta dengan kedua angka. Isian tetap milik Anda: periksa koordinat atau angka yang dimasukkan. Warning tidak memblokir simpan/export/submit; aturan validasi lainnya tetap berlaku. Hasil adalah perkiraan dari koordinat WGS84 pada model bumi bulat, bukan luas legal atau hasil survei.

### Lampiran dan dokumen

Format lampiran: PDF, JPG/JPEG, PNG, dan WebP. Setiap bab memiliki batas maksimal 10 lampiran aktif dan total 15 MiB. Batas mencakup gambar inline, appendix, peta, site plan, foto, dan PDF.

Lampiran dapat ditempatkan sebagai **Lampiran bab** atau **Di bawah teks acuan**. Untuk posisi inline, pilih field/teks acuan dan isi caption. WebP asli tetap disimpan; normalisasi hanya digunakan pada salinan render.

Setelah bab lengkap, gunakan **Buat Word + PDF**. Hasil hanya memuat bab dan lampiran bab tersebut. Export tidak mengubah status proposal. Dokumen generated sendiri dapat diunduh dari wadah akun, termasuk saat approval pending/dicabut. Unduhan memeriksa owner, private storage dan integritas; hak ini tidak membuka data proposal atau lampiran mentah.

Ekspor baru memakai sampul dengan nama institusi/pemohon, judul bagian yang konsisten, tabel identitas, paragraf narasi, gambar proporsional dan nomor halaman. Caption/sumber gambar dipertahankan; nama file menjadi keterangan pengganti jika caption kosong. Word tetap bisa disunting melalui styles judul dan paragraf. Pada PDF, tabel dengan satu baris sangat panjang dapat ditampilkan sebagai rincian per kolom agar isi tidak terpotong.

Untuk draft lengkap yang belum terkunci, klik **Buat Word + PDF** kembali untuk memperoleh format rapi. Dokumen lama tetap tersimpan. Proposal yang sudah submitted/locked mengikuti proses revisi; pembaruan format tidak membuka kuncinya. Contoh lokal hasil format baru berada di `docs/export_sample/rapi`, terpisah dari contoh asli.

Pemohon approved dapat memakai **Hapus dokumen** pada wadah akun atau daftar hasil generate di wizard. Periksa bab, format dan tanggal, lalu konfirmasi. Hanya berkas yang dipilih dilepas secara permanen; format lain, proposal dan lampiran tetap tersimpan. Versi lama/tidak tersedia dapat dibersihkan, tetapi tidak dapat diunduh sebagai hasil terkini. Snapshot dan audit tetap disimpan. Dokumen submitted/needs_revision tidak dapat dihapus; pending/dicabut tetap hanya melihat/mengunduh hasil valid. Draft lengkap yang belum locked dapat digenerate ulang secara eksplisit setelah penghapusan. Lihat [aturan penghapusan](kkprl-document-deletion-spec.md).

### Mengirim proposal

Klik **Kirim untuk Ditinjau** setelah semua bab relevan lengkap. Sistem membuat snapshot, Word, dan PDF per bab aktif sebelum mengunci proposal.

Status `submitted` berarti dokumen pendukung telah dikirim untuk review. Status ini bukan penerbitan izin. Setelah terkunci, payload dan lampiran tidak dapat diubah.

Jika petugas meminta revisi, login akun approved dan pilih salinan baru berlabel `rev-1`, `rev-2`, dan seterusnya; proposal submitted asli tetap tidak berubah.

## Petugas Filament

Panel: `Layanankkprl` (`/layananruanglaut`). Setelah login, buka menu **Proposal KKPRL > Review Proposal**. Menu approval berada pada **Proposal KKPRL > Approval Edit Darurat**. Hak akses berasal dari Filament Shield/Laravel Authorization, bukan nama role.

Pada host lokal, halaman login biasanya tersedia di **`/layananruanglaut/login`**. Pada production, gunakan host resmi yang dikonfigurasi untuk panel tersebut. Jika menu tidak terlihat, akun belum memiliki permission yang diperlukan.

Permission minimum:

- `Review:KkprlProposal` — melihat dan meninjau proposal.
- `RequestEdit:KkprlProposal` — meminta edit darurat scoped.
- `ApproveEdit:KkprlProposal` — menyetujui/menolak approval edit dan menentukan kedaluwarsa.
- `RunEditSession:KkprlProposal` — menjalankan sesi edit yang telah disetujui.
- `Download:KkprlProposalDocument` — mengunduh Word/PDF.
- `Download:KkprlProposalAttachment` — mengunduh lampiran.

Role apa pun dapat menjadi Petugas B bila memiliki permission approval. Petugas A tidak boleh menyetujui atau menolak permintaannya sendiri, termasuk saat memiliki akses super-admin.

### Mengelola akun pemohon

Menu **Proposal KKPRL > Akun pemohon** tersedia pada panel Layanankkprl. Role yang disepakati adalah `admin`, `pimpinan`, `super_admin`; seeder khusus memberi permission pada role tersebut, tanpa memberi kewenangan ke semua petugas.

- `ViewAny:KkprlApplicant` membuka daftar; `View:KkprlApplicant` membuka detail.
- `Approve:KkprlApplicant` menyetujui/mencabut builder. Approval akun tidak mengubah `User.status` atau approval edit proposal.
- `ResetPassword:KkprlApplicant` mengganti password pemohon dan mengakhiri seluruh sesi lama; tidak dapat menarget akun petugas/super_admin.
- `AssignOwner:KkprlApplicant` menetapkan proposal legacy setelah bukti kepemilikan diperiksa manual. Pilih akun, masukkan ID proposal yang diverifikasi, alasan dan referensi bukti. Seluruh rantai revisi ditetapkan atomik; owner akun lain tidak dapat ditimpa. Tiket atau kemiripan email/HP saja tidak cukup.

Tindakan dicatat dengan actor, target, waktu dan jenis tindakan tanpa password. Persyaratan bukti mengikuti prosedur organisasi; aplikasi tidak menambahkan persyaratan identitas baru.

### Review normal

1. Buka **Review Proposal**, cari nomor tiket atau field review yang tersedia, lalu periksa Bab aktif, lampiran, dokumen, dan riwayat review.
2. Gunakan **Minta revisi** untuk memberi catatan. Sistem membuat salinan revisi; proposal submitted asli immutable.
3. Download hanya memakai action privat dan permission download yang sesuai.

### Edit darurat scoped

1. Petugas A mengajukan edit pada revisi draft dengan alasan dan field/section yang diizinkan.
2. Petugas B dengan `ApproveEdit:KkprlProposal` menyetujui atau menolak dan menentukan `expires_at`.
3. Setelah disetujui, hanya Petugas A yang dapat memulai satu sesi. Approval menjadi one-use.
4. Setiap perubahan wajib memiliki alasan dan dicatat append-only dengan before/after, actor, role, permission, waktu, dan request ID.
5. Sesi berakhir saat dibatalkan, kedaluwarsa, atau revisi dikirim. Approval baru diperlukan untuk sesi berikutnya.

Satu approval atau sesi edit aktif hanya boleh ada untuk satu revisi. Permintaan paralel ditolak agar scope dan riwayat perubahan tetap deterministik.

## Operasional

Artefak dan lampiran baru berada pada disk privat `kkprl_private` tanpa serving route. Retensi tidak menghapus data secara otomatis pada MVP; penghapusan/arsip harus mengikuti kebijakan organisasi. Jalankan preflight sebelum release:

```text
rtk php artisan kkprl:preflight --strict
```

Untuk UAT manual, gunakan [checklist UAT](kkprl-uat-checklist.md). Untuk keputusan arsitektur, lihat [ADR renderer dan artefak privat](decisions/ADR-009-kkprl-renderer-and-private-artifacts.md), [keputusan generasi sinkron berbatas](decisions/ADR-013-bounded-synchronous-document-generation.md), dan [kebijakan retensi](kkprl-data-retention.md).

### Transisi akun (belum dijalankan pada database pengguna)

Untuk deployment yang kelak diotorisasi, buat backup DB/private storage, terapkan migration baru dengan `rtk proxy php artisan migrate --force`, kemudian permission khusus dengan `rtk proxy php artisan db:seed --class=KkprlApplicantPermissionSeeder --force`. Jangan memakai `migrate:fresh` atau seeder keseluruhan. Pastikan generated assets Filament sesuai vendor terpasang (`rtk proxy php artisan filament:assets`, dengan backup asset existing terlebih dahulu). Verifikasi role mapping dan akun pengelola sebelum cutover; data legacy tetap tanpa owner sampai verifikasi manual. Jangan rollback skema akun setelah ada akun/audit/assignment tanpa rencana pemulihan dari backup. Detail ada pada ADR-026 dan laporan validasi akun.


## Peta lokasi pada Word/PDF Bab 1

Setelah Bab 1 lengkap, generate Word/PDF menyertakan satu halaman landscape berisi peta dari koordinat yang sudah Anda simpan. Bentuk titik/garis/area, nomor titik, ukuran dan skala tercantum. Ukuran area menggunakan ha, garis km, dua desimal. Jika berbeda dari isian Dimensi, panel peta meminta Anda memeriksa koordinat atau dimensi; sistem tidak mengubah isian tersebut.

Peta utama baru memakai OpenStreetMap untuk konteks jalan/bangunan, dengan grid, batas tapak dan nomor titik. Detail bergantung kelengkapan data OSM wilayah tersebut. Sistem memilih skala otomatis mulai 1:500, lalu 1:1.000, 1:2.000, 1:2.500 dan seterusnya agar semua titik muat. Pada 1:500, 1 cm di kertas mewakili sekitar 5 m di lapangan; pada 1:1.000 sekitar 10 m. Angka berlaku di tengah peta pada ukuran cetak asli 100%. Mengubah ukuran gambar Word atau memakai fit-to-page mengubah skala angka; skala grafis tetap ikut berubah proporsional. Ini skala tampilan, bukan klaim ketelitian survei atau persyaratan hukum.

Inset memperlihatkan wilayah sekitar dengan cakupan lebih luas, paling dekat 1:50.000 untuk citra WorldCover 2021 sekitar 10 m. Kotak biru berarti cakupan peta utama; tanda + adalah pusat cakupan. Keduanya bukan batas atau perluasan permohonan. Koordinat, ukuran geometri dan isian Dimensi tetap sama; pembesaran hanya mengubah tampilan di kertas.

Isi “Sumber data lokasi (opsional)” bila ingin mencantumkan sumber koordinat, misalnya survei lapangan. Kosong berarti baris sumber pengguna tidak ditampilkan. Atribusi OpenStreetMap dan citra otomatis tetap wajib. Citra hanya pada inset dan tidak menunjukkan kondisi terkini. Peta jalan juga tersedia pada layar. Logo dan overlay coastline khusus tidak ditambahkan. Lihat [ADR-035](decisions/ADR-035-openstreetmap-main-map-with-satellite-inset.md) dan [validasi OSM](kkprl-osm-main-map-validation.md).

Jika pengambilan peta dasar gagal, generate menampilkan error dan dapat dicoba ulang. Hasil lama tetap tersimpan. Untuk dokumen lama tanpa peta jalan utama, generate ulang draft yang lengkap dan belum locked; proposal submitted mengikuti revisi existing. Pemohon pending hanya membaca hasil generated miliknya dan tidak dapat generate peta/dokumen. Petugas tidak memperoleh permission baru.

Pengelola shared hosting tidak perlu memasang GIS atau layanan baru. Pastikan PHP GD/FreeType, font vendor, private storage/cache, `APP_URL` publik yang benar dan HTTPS keluar ke `tile.openstreetmap.org` serta WMTS Terrascope tersedia. Default `KKPRL_MAP_MAIN_BASEMAP=openstreetmap` untuk utama dan `KKPRL_MAP_BASEMAP=worldcover-2021` untuk inset. Nilai `none` menonaktifkan provider frame masing-masing secara eksplisit; bukan fallback otomatis. Bersihkan cache konfigurasi melalui prosedur deployment existing jika mengubah opsi. Ikuti kebijakan tile OSM: hanya frame yang diminta, cache dan atribusi wajib, tanpa prefetch/arsip tile offline. Hosting aktual belum diverifikasi. Lihat [ADR-035](decisions/ADR-035-openstreetmap-main-map-with-satellite-inset.md) dan [validasi](kkprl-osm-main-map-validation.md).

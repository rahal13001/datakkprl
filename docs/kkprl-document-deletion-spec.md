# Spesifikasi penghapusan berkas dokumen KKPRL

Tanggal: 12 Oktober 2026. Pengguna meminta penghapusan hasil generate yang tidak dipakai agar daftar jelas dan ruang penyimpanan berkurang. Pengguna menetapkan bahwa hanya dokumen draft/revisi terbuka yang boleh dihapus; arsip final submitted/needs_revision tetap disimpan.

## Perilaku dan batas

- Pemohon approved dapat menghapus satu berkas Word atau PDF miliknya dari portal akun dan wizard. Format lain tidak ikut dihapus. Konfirmasi menjelaskan penghapusan berkas permanen dan data proposal/lampiran tetap ada.
- Akun pending tetap hanya melihat/mengunduh hasil generate yang valid miliknya. Penghapusan memerlukan approval dan sesi pemohon yang masih berlaku, termasuk saat request Livewire langsung.
- Draft/revisi terbuka menampilkan hasil generate yang valid maupun versi lama/tidak tersedia, beserta tanggal dan status unduh. Versi lama tidak menjadi jalan melewati pemeriksaan snapshot/hash.
- Snapshot, manifest terenkripsi, identitas dokumen, proposal, chat, lampiran, revisi, dan riwayat tetap ada. Tombstone menandai berkas yang dihapus dan audit append-only mencatat actor, dokumen, proposal, format, serta waktu. Metadata audit tidak memuat isi berkas atau kredensial.
- Penghapusan melepaskan berkas pada private storage lokal, bukan sekadar menyembunyikannya. Storage path harus sesuai identitas proposal dan dokumen; traversal, symlink, disk publik, atau berkas yang direferensikan dokumen lain ditolak.
- Penghapusan berulang idempotent. Berkas yang sudah hilang tetap dapat dibereskan dari daftar. Jika storage gagal menghapus, jangan laporkan sukses; transaksi metadata/audit dibatalkan dan pengguna dapat mencoba lagi.
- Generate ulang yang secara eksplisit dimulai setelah penghapusan boleh mengaktifkan kembali artefak pada identitas snapshot yang sama. Snapshot immutable dan audit penghapusan tetap ada. Generate yang sudah berjalan sebelum penghapusan tidak boleh mengaktifkan kembali berkas diam-diam.
- Tidak ada bulk delete, penghapusan otomatis, permission petugas baru, endpoint publik baru, layanan, dependency, atau instalasi sistem tambahan. Tidak menjalankan operasi hapus pada dokumen pengguna selama implementasi.

## Matriks akses

| Aktor/keadaan | Lihat/unduh valid milik sendiri | Hapus berkas draft sendiri | Hapus arsip final | Dokumen akun lain |
|---|---|---|---|---|
| Pemohon approved, sesi berlaku | Ya | Ya, dengan konfirmasi | Tidak | Tidak |
| Pemohon pending/approval dicabut | Ya | Tidak | Tidak | Tidak |
| Sesi logout/reset atau tiket/HP lama | Tidak | Tidak | Tidak | Tidak |
| Petugas | Permission download existing | Tidak melalui aksi pemohon | Tidak | Sesuai aturan download existing |

## Rencana migrasi

Migration baru menambahkan nullable `deleted_at` dan `deleted_by_applicant_id` pada `kkprl_proposal_documents`. Dokumen existing tetap aktif dengan nilai null. Unique identity snapshot/manifest, encrypted payload, foreign key proposal, dan migration lama tidak berubah. Tombstone tidak memakai Eloquent SoftDeletes agar jejak snapshot tetap ditemukan petugas dan identitas generate tetap unik. Actor juga tersimpan dalam audit append-only. Migrasi hanya dijalankan di fixture SQLite terisolasi, bukan database pengguna.

Database dan filesystem tidak memiliki transaksi atomik bersama. Mutasi memakai row lock proposal, akun, dan dokumen; metadata/audit ditulis sebelum unlink dalam transaksi yang sama. Kegagalan unlink membatalkan transaksi. Jika commit gagal setelah unlink, berkas tidak tersedia dan penghapusan dapat diulang untuk menyelesaikan tombstone. Tidak ada upaya menghidupkan kembali berkas yang sudah dihapus atau menambahkan layanan rekonsiliasi.

## Tahap implementasi dan acceptance criteria

1. Backend: migration additive, service ownership/approval/lock/path, tombstone, audit, dan guard generate/download. Buktikan berkas terhapus, snapshot tetap, sibling/attachment/proposal tetap, idempotency, penolakan lintas akun/pending/staff/locked, kegagalan storage, path tampering, serta generate ulang dan operasi yang berjalan bersamaan.
2. UI: daftar dokumen bersama pada portal/wizard, tanggal/status, konfirmasi dan feedback, versi lama dapat dihapus oleh pemohon approved; pending tidak mendapat metadata proposal mentah atau aksi baru. Buktikan aksi Livewire langsung tidak bypass, daftar berubah, download hasil hapus 404, desktop/mobile dan empty state.
3. Verifikasi/dokumentasi: tes relevan lalu regresi serial, Pint, build Vite karena Blade berubah, browser fixture tanpa API berbayar/database pengguna, ADR-034, panduan/security/spec, laporan validasi dan pembaruan Graphify utama dari kode serta dokumentasi.

## Struktur dan perintah

Kode mengikuti service KKPRL di `app/Services`, model di `app/Models`, komponen Livewire di `app/Livewire`, Blade di `resources/views`, migration additive di `database/migrations`, dan tes fitur di `tests/Feature`. Gunakan tipe eksplisit, Eloquent parameterized query, Blade escaping, dan format Pint. Contoh batas service: `public function delete(int $documentId): void` menerima ID, bukan path/owner dari pengguna.

- Tes scope: `rtk proxy php artisan test --compact --filter='KkprlProposalDocumentDeletionTest|KkprlProposalDocumentGenerationTest|KkprlProposalDocumentDownloadTest|KkprlApplicantAccessTest'`.
- Regresi serial: `rtk proxy php artisan test --compact` (SQLite memory, Storage fake, HTTP/AI fake).
- Format: `rtk proxy php vendor/bin/pint --test` dengan berkas PHP scope.
- Build: `rtk proxy npm run build`.
- Whitespace: `rtk git diff --check` untuk berkas scope dan verifikasi berkas untracked.

Working tree existing dipertahankan. Tidak commit/push/deploy, tidak migrate:fresh pada database pengguna, dan tidak menambah kewenangan di luar keputusan bisnis.

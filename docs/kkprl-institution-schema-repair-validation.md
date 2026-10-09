# Validasi dan runbook perbaikan kolom institusi

Tanggal: 9 Oktober 2026. [Spesifikasi](kkprl-institution-schema-repair-spec.md), [ADR-029](decisions/ADR-029-additive-institution-schema-repair.md).

## Diagnosis

Laporan hosting: autosave proposal gagal dengan SQLSTATE 42S22 / MySQL 1054, kolom `institution_name` tidak ada. Hasil query pengguna: migration `2026_08_24_000002_add_institution_protection_to_kkprl_proposals` tercatat batch 23; pencarian kolom `institution_name` dan `institution_name_encrypted` pada tabel aplikasi menghasilkan nol baris. Ini membuktikan ketidaksesuaian skema dan riwayat migration, bukan penyebab historisnya. Tidak ada koneksi agent ke database hosting.

## Perubahan

Migration baru memeriksa dan menambahkan setiap kolom yang hilang secara terpisah. Kedua kolom TEXT nullable, tanpa penulisan ulang row, backfill atau penghapusan kolom. Migration lama dan ledger-nya tidak diubah. `down()` mempertahankan kolom/data. Model dan service autosave tidak diubah.

Versi dari composer.lock saat audit: Laravel 13.26.1, Livewire 4.4.1, Filament 5.7.6. PHP pengujian Laragon 8.4.8; PHPUnit 12.5.24. Tidak ada dependency baru.

## Pengujian aktual

| Pemeriksaan | Perintah | Hasil aktual |
|---|---|---|
| RED sebelum migration baru dibuat | `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlInstitutionSchemaRepairTest.php` | Exit 2; 7 tests, 2 assertions, 7 errors karena file repair belum ada. Assertion reproduksi QueryException autosave pada kolom hilang sudah berjalan |
| GREEN perbaikan | `rtk proxy php vendor/bin/phpunit tests/Feature/KkprlInstitutionSchemaRepairTest.php` | Exit 0; 7 tests, 180 assertions PASS; 1,395 detik |
| Format PHP | `rtk proxy php vendor/bin/pint --test database/migrations/2026_10_09_000001_repair_kkprl_proposal_institution_columns.php tests/Feature/KkprlInstitutionSchemaRepairTest.php` | Exit 0; 2 files PASS |
| Regresi penuh | `rtk proxy php vendor/bin/phpunit --log-junit=storage/logs/kkprl-institution-schema-repair-junit.xml` | Exit 0; 476 tests, 2642 assertions PASS; 4 menit 21,142 detik |
| Whitespace tracked | `rtk proxy git diff --check` | Exit 0; Git memberi peringatan konversi CRLF existing, tanpa kesalahan whitespace |
| Whitespace sumber baru | Pemeriksaan Python enam sumber perubahan | Tidak ada trailing whitespace |

Fixture memakai SQLite :memory:, session/cache array dan private storage fake. Tes baru melarang request HTTP eksternal. Tes mencakup kedua kolom hilang, masing-masing satu kolom hilang, skema utuh, eksekusi dua kali, penjadwalan migration baru oleh Artisan walaupun migration lama tercatat selesai, autosave institusi terenkripsi, down yang mempertahankan data, owner/rantai revisi, chat/audit, lampiran/dokumen private, snapshot dan attachment manifest hash.

Suite penuh juga menjalankan regresi akses akun/panel, Bab 1–5, draft/AI fake, pasut/grafik, GMRT fake, ekspor Word/PDF, download/integritas dan workflow petugas. Tidak ada panggilan Gemini berbayar. `.env`, database pengguna dan hosting tidak diubah. JUnit lokal disimpan pada storage/logs/kkprl-institution-schema-repair-junit.xml (tidak dimasukkan ke Graphify).

Pemanggilan PHP pertama dalam sandbox gagal pada interop WSL `UtilBindVsockAnyPort`; pengujian selanjutnya dijalankan melalui eskalasi interop PHP Laragon yang disetujui. Ini bukan kegagalan aplikasi/database. Perintah migration produksi pada runbook di bawah tidak dijalankan agent.

## Penerapan oleh operator hosting

1. Backup database melalui panel hosting yang tersedia. Pilih release/folder Laravel dan koneksi database yang benar.
2. Unggah file baru `database/migrations/2026_10_09_000001_repair_kkprl_proposal_institution_columns.php` melalui proses deployment existing. Jangan menimpa migration lama untuk memaksanya berjalan ulang.
3. Pratinjau migration khusus ini, kemudian jalankan pada aplikasi yang memakai database hosting tersebut. Contoh di workspace dengan wrapper RTK:

```bash
rtk proxy php artisan migrate --pretend --path=database/migrations/2026_10_09_000001_repair_kkprl_proposal_institution_columns.php
rtk proxy php artisan migrate --force --path=database/migrations/2026_10_09_000001_repair_kkprl_proposal_institution_columns.php
```

RTK adalah wrapper perintah workspace, bukan kebutuhan runtime aplikasi. Hosting memakai PHP dan Artisan yang sudah tersedia; tidak perlu memasang RTK atau program sistem lain. Jalankan hanya satu operator/proses migration. Jika DDL berhenti setelah satu kolom berhasil, ulangi migration yang sama setelah memperbaiki penyebab kegagalan; kolom yang sudah ada dilewati.

4. Pilih database aplikasi pada phpMyAdmin, lalu verifikasi dengan query baca saja:

```sql
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'kkprl_proposals'
  AND COLUMN_NAME IN ('institution_name', 'institution_name_encrypted');

SELECT migration, batch
FROM migrations
WHERE migration IN (
  '2026_08_24_000002_add_institution_protection_to_kkprl_proposals',
  '2026_10_09_000001_repair_kkprl_proposal_institution_columns'
);
```

Harus ada dua kolom TEXT nullable dan riwayat migration baru. Riwayat lama batch 23 tetap dipertahankan. Jika tabel migration tidak ditemukan dalam information_schema, pilih database aplikasi terlebih dahulu.

5. Login sebagai pemohon approved, buka draft miliknya yang belum locked, simpan satu isian institusi, lalu muat ulang. Pastikan nilai tetap ada dan error 1054 berhenti. Dokumen existing harus tetap dapat diunduh menurut aturan integritas/aksesnya. Jangan memakai submitted proposal untuk uji edit; jangan memanggil AI/GMRT untuk verifikasi perbaikan skema ini.

Perbaikan menambahkan tempat penyimpanan, bukan memulihkan input yang gagal tersimpan. Jika aplikasi harus di-rollback, pertahankan kedua kolom dan jangan menghapus riwayat migration lama. Pemulihan database dari backup adalah prosedur operator terpisah, bukan `migrate:fresh` atau rollback massal.

## Graphify dan batas verifikasi

Cakupan pembaruan indeks utama adalah enam sumber: migration repair, tes baru, spesifikasi repair, laporan ini, ADR-029 dan tambahan rujukan pada spesifikasi builder. AST kode dan semantic dokumentasi memakai isi sumber aktual; bagian spesifikasi builder yang tidak berubah dipertahankan setelah verifikasi. Cakupan tetap indeks proyek/KKPRL, bukan rebuild semua berkas repositori. Relasi parser ambigu tidak dianggap bukti runtime.

Hasil audit akhir jumlah node/relasi, endpoint, relasi berarah/parallel, hash sumber, manifest dan kesetaraan graph JSON/HTML dicatat pada [GRAPH_REPORT](../graphify-out/GRAPH_REPORT.md) serta graphify-out/.graphify_update.json. Pemeriksaan harus mempertahankan relasi di luar scope dan memastikan simbol migration/test/ADR-029 dapat ditemukan. Query sebelum perubahan digunakan sebagai peta, bukan bukti indeks telah diperbarui.

Review kode mencakup penambahan nullable tanpa posisi `after`, penjagaan kolom terpisah untuk skema parsial, tidak adanya UPDATE/backfill/drop atau perubahan auth, dan down tanpa kehilangan data. UI, asset frontend dan aturan kelengkapan Bab 1 tidak berubah; build/browser tidak dijalankan untuk perubahan skema ini. MySQL hosting dan autosave pada hosting belum diuji agent. Tidak dilakukan commit, push atau deploy.

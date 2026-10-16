# Validasi penghapusan dokumen hasil generate

Tanggal: 12 Oktober 2026. Scope: [spesifikasi](kkprl-document-deletion-spec.md), keputusan arsip final tetap tersimpan, dan [ADR-034](decisions/ADR-034-audited-generated-document-file-deletion.md).

## Implementasi

Migration menambah nullable marker. Service memeriksa owner/approval/lock/path, melepas berkas private dan menulis audit append-only. Unique identity dan snapshot terenkripsi tetap ada. Download menolak tombstone. Generate eksplisit setelah hapus dapat membuat ulang berkas; perubahan marker audit selama batch membatalkan persistence. Format lain, lampiran, payload, revisi dan arsip locked tidak ikut dihapus.

Portal/wizard memakai daftar bersama dengan tanggal, status unduh dan konfirmasi satu berkas. Approved melihat versi lama/missing untuk dibersihkan; pending tetap hanya melihat dokumen generated valid sendiri. Panel petugas read-only menampilkan waktu penghapusan dan menyembunyikan unduhan tombstone. Tidak ada permission baru.

## Perintah dan hasil aktual

PHP Laragon 8.4.8; composer.lock: Laravel 13.26.1, Livewire 4.4.1, Filament 5.7.6. Semua shell memakai RTK. PHPUnit serial memakai SQLite memory, Storage fake dan HTTP/AI fake; tidak ada Gemini berbayar atau penghapusan data pengguna.

| Tahap | Perintah | Hasil |
|---|---|---|
| RED fitur | `rtk proxy php artisan test --compact --filter=KkprlProposalDocumentDeletionTest` | 13 gagal, 2 assertions, 29,63 s; service/action/migration belum ada. Fixture mock final dikoreksi menjadi instance proxy. |
| GREEN backend | `rtk proxy php artisan test --compact --filter='deleting_one_file\|deletion_is_idempotent\|failed_storage_deletion\|public_disk_traversal\|explicit_regeneration\|deletion_during_render\|additive_migration_preserves_existing_document'` | 7 lulus, 43 assertions, 3,16 s |
| UI pertama | `rtk proxy php artisan test --compact --filter=KkprlProposalDocumentDeletionTest` | 10 lulus, 3 gagal, 75 assertions, 6,60 s; exception model diterjemahkan ke HTTP 404 pada action dan fixture versi sesi diperbaiki. |
| RED batch dua format | `rtk proxy php artisan test --compact --filter=deletion_during_first_format` | 1 gagal, 2 assertions, 1,25 s; batch dapat menghidupkan format kedua. Guard audit batch ditambahkan. |
| GREEN integrasi | `rtk proxy php artisan test --compact --filter='KkprlProposalDocumentDeletionTest\|KkprlProposalDocumentGenerationTest\|KkprlProposalDocumentDownloadTest\|KkprlApplicantAccessTest'` | 45 lulus, 404 assertions, 42,38 s |
| Regresi pertama | `rtk proxy php artisan test --compact` | 529 lulus, 3.192 assertions, 292,34 s |
| RED error storage/urutan | `rtk proxy php artisan test --compact --filter='test_storage_exception_shows_retry_message\|test_equal_generation_times_show_newest'` | 2 gagal, 2 assertions, 1,69 s. Exception adapter menghasilkan error dan timestamp sama tidak menempatkan berkas terbaru lebih dulu. |
| GREEN fitur akhir | `rtk proxy php artisan test --compact --filter=KkprlProposalDocumentDeletionTest` | 19 lulus, 107 assertions, 6,42 s |
| Regresi akhir PHP | `rtk proxy php artisan test --compact` | 531 lulus, 3.202 assertions, 90,34 s |
| Render setelah jarak mobile | `rtk proxy php artisan test --compact --filter=KkprlProposalDocumentDeletionTest` | 19 lulus, 107 assertions, 5,85 s |
| Format akhir | `rtk proxy php vendor/bin/pint --test <10 berkas PHP cakupan>` | PASS, 10 berkas. Scope ditulis di bawah. |
| Build akhir | `rtk proxy npm run build` | PASS, Vite 7.3.6, 59 modules, 3,67 s. CSS 110,70 kB dan JS 54,42 kB. |
| Whitespace diff | `rtk git diff --check` | PASS. Pemeriksaan 19 sumber menemukan whitespace existing pada wizard; tidak diubah massal. Berkas baru tidak memiliki trailing whitespace. |

Regresi akhir mencakup Bab 1–5, pasut/grafik, GMRT fake, Word/PDF, download private dan workflow petugas. Sesudah regresi PHP, jarak bawah region dokumen ditambah pada mobile untuk menyediakan ruang di dekat tombol chat. Build dan pemeriksaan browser diulang untuk perubahan tampilan ini.

Perintah format lengkap:

```sh
rtk proxy php vendor/bin/pint --test app/Services/KkprlProposalDocumentDeletionService.php app/Services/KkprlProposalDocumentListingService.php app/Livewire/KkprlApplicantPortal.php app/Livewire/KkprlProposalWizard.php app/Services/KkprlProposalDocumentService.php app/Http/Controllers/KkprlProposalDocumentDownloadController.php app/Models/KkprlProposalDocument.php app/Filament/Layanankkprl/Resources/KkprlProposals/RelationManagers/DocumentsRelationManager.php database/migrations/2026_10_12_000001_add_document_deletion_metadata.php tests/Feature/KkprlProposalDocumentDeletionTest.php
```

## Browser aktual

Chrome DevTools MCP, konteks terisolasi `kkprl-document-deletion-20261012`, server PHP lokal port 8772. Harness QA di folder ignored `storage/framework/testing` memakai SQLite fixture terpisah, root storage/session/view terpisah, akun sintetis dan HTTP backend yang menolak request tak dipalsukan. Route QA hanya milik router server pengujian, bukan `routes/web.php`; bukan fitur produksi dan tidak diindeks Graphify.

- Desktop 1440 × 1000: daftar approved menampilkan tanggal/status; versi lama tanpa link unduh. Konfirmasi batal tidak mengirim request hapus dan berkas tetap ada. Konfirmasi setuju mengirim Livewire POST HTTP 200, menghapus item dan menampilkan pesan `role=status`.
- Mobile 390 × 844: portal dan wizard tidak overflow horizontal (lebar dokumen 390 px); tombol hapus 44 px. Snapshot aksesibilitas memiliki urutan h1/h2/h3 dan label tombol menyertakan format, bab, waktu dan status. Screenshot inline diperiksa; screenshot tidak diklaim sebagai berkas laporan tersimpan.
- Wizard mobile: hapus PDF versi lama melalui konfirmasi, Livewire HTTP 200; format lain tetap muncul. Jarak bawah region 80 px memberi ruang pada akhir daftar. Klik tombol hapus sesudah perubahan tetap membuka konfirmasi; dibatalkan tanpa menghapus berkas.
- Portal setelah berkas terakhir dihapus: `Belum ada dokumen.`; proposal tetap terlihat dan dapat dibuka. URL dokumen PDF yang sebelumnya valid menjadi HTTP 404.
- Storage fixture turun dari 3.514.533 menjadi 2.635.907 lalu 1.757.252 byte setelah dua penghapusan PDF; jumlah marker/audit bertambah masing-masing satu. Ini mengukur fixture private, bukan kapasitas hosting pengguna.
- Akun pending hanya melihat PDF valid miliknya dan logout; tanpa hapus/builder/referensi proposal. Akun pending kosong menampilkan empty state pada mobile dan desktop, tanpa link atau tindakan selain logout.
- Akun approved dengan proposal submitted: PDF tetap ditawarkan untuk unduh, tanpa hapus. Penolakan service/request langsung dibuktikan tes otomatis.
- Generate Bab 2 secara eksplisit sesudah penghapusan berhasil via Livewire HTTP 200 dan menampilkan Word/PDF baru. Tidak ada AI berbayar atau operasi GMRT backend.
- Portal approved/pending/empty/locked: tidak ditemukan console error/warning. Wizard memiliki satu jenis warning (dua kemunculan) karena fixture sintetis `Terisi` pada input angka. Tidak ada error JavaScript yang ditemukan; validasi ini tidak mengklaim semua isi fixture memenuhi semantik template.

## Review

Pemeriksaan mencakup otorisasi ulang di transaction, owner dan lock, private path/alias/shared reference, rollback marker/audit saat adapter gagal (termasuk exception), batch generate yang sedang berjalan, integritas unduhan dan pelestarian snapshot. Daftar mengurutkan `generated_at DESC, id DESC` untuk timestamp sama. Tidak ditambahkan permission petugas untuk menghapus, data plaintext sensitif atau dependency sistem. Tombol pending/locked disembunyikan dan pemanggilan langsung tetap ditolak.

## Graphify

Cakupan pembaruan adalah 19 sumber: 10 PHP, 3 Blade dan 6 Markdown. Ekstraksi kode dan semantik dokumentasi/UI memakai host agent tanpa API Gemini berbayar. Manifest/audit utama mencatat hash, arah/multiplicity relasi, endpoint, pelestarian luar scope, simbol baru dan batas relasi ambigu. Hasil final disimpan di `graphify-out/document-deletion-audit.json`; cakupan ini tidak mengklaim seluruh repositori diindeks.

Verifier read-only dijalankan dengan `rtk proxy /home/rahal/.local/share/uv/tools/graphifyy/bin/python3 /tmp/kkprl-document-deletion-graphify/verify_scope.py`: PASS, 19 hash sumber dan 4 hash artefak cocok, 3.807 node, 9.762 relasi, 174 komunitas dan 8 hyperedge. Sebanyak 9.205 relasi luar scope dipertahankan persis; HTML mempertahankan arah/multiplicity, simbol baru ditemukan, endpoint/member hyperedge/path sumber hilang 0. Ada 928 relasi ambigu termasuk 113 kandidat pemanggilan baru; 374 simbol eksternal/tidak teresolusi tanpa source path bukan endpoint hilang. Hitungan tersebut dan resolver placeholder lama yang kini terhubung ke model konkret dicatat dalam audit, tanpa menyembunyikan keterbatasan parser. Literal password fixture pengujian tidak ada dalam graph.json.

## Batas

Marker berkas tidak mengubah hash. Migration lama dan data existing tidak dihapus. Migration hanya diuji pada database terisolasi; belum diterapkan di database pengguna. Deploy harus menjalankan migration additive sebelum UI/service baru dipakai. Unlink dan commit DB bukan transaksi atomik; cara retry ada pada ADR-034. Salinan download atau backup hosting tidak termasuk target hapus. Hosting aktual belum diuji.

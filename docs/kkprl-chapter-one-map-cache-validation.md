# Perbaikan cache peta Bab 1 pada MySQL

Tanggal: 12 Oktober 2026. Scope: kegagalan ekspor Bab 1 di Laragon setelah peta ditambahkan. Working tree sebelumnya dipertahankan; tidak ada commit/push/deploy.

## Diagnosis dan spesifikasi sebelum implementasi

Data lokal diperiksa baca saja, dengan query write ditolak. Bab 1 berstatus complete, missing/errors kosong; bab lain incomplete. Dengan cache array dan HTTP fake, rendering data tersebut menghasilkan Word dan PDF di memori tanpa menyimpan file proposal, tanpa write database pengguna dan tanpa AI. Nilai identitas, narasi dan koordinat tidak dicetak atau dijadikan fixture/Graphify.

Konfigurasi runtime actual Laragon terverifikasi `cache.default=database`, driver MySQL, `cache.value` bertipe MEDIUMTEXT dengan collation utf8mb4_unicode_ci. `KkprlWorldCoverTiles` menyimpan PNG biner langsung; `DatabaseStore` MySQL melakukan PHP serialize tanpa Base64. Byte PNG bukan UTF-8 valid, sedangkan `cache.value` merupakan kolom teks. Reproduksi memakai database MySQL fixture baru berawalan `kkprl_tile_test_` acak, tabel UTF-8 dan strict mode: PNG ditolak SQLSTATE HY000/error 1366, Base64 diterima dan decode identik. Database fixture dihapus hanya setelah marker kepemilikannya cocok; database pengguna tidak ditulis.

Perbaikan yang direncanakan: hanya tile publik dikodekan Base64 sebelum cache put, decode strict dan batas ukuran sebelum validasi PNG/cache hit. Namespace cache versi baru menghindari membaca format binary lama; cache lama dibiarkan kedaluwarsa, tanpa flush. Batas provider, private overlay, snapshot, otorisasi, owner dan lock tetap berlaku. Tidak ada migration, perubahan `.env`, vendor, layanan atau dependency baru.

Matriks akses sama dengan [spesifikasi peta](kkprl-chapter-one-map-spec.md): pending/cross-owner/locked tidak boleh generate; approved hanya draft lengkap sendiri. Pengunduhan dokumen private milik sendiri tetap mengikuti integritas. Bab lain yang incomplete tidak menghalangi ekspor Bab 1. Peta tetap tertanam pada Word/PDF; ekspor PNG terpisah belum menjadi fitur pengguna.

Acceptance criteria: tes merah mereproduksi kegagalan kolom cache UTF-8 dengan serializer MySQL actual; setelah fix cache tersimpan sebagai UTF-8 dan second request menghasilkan byte PNG identik tanpa HTTP tambahan; cache Base64/PNG rusak dan terlalu besar ditolak/refetch; pengujian MySQL fixture actual serta regresi per bab, otorisasi dan dokumen lulus. Tidak menurunkan validasi isian atau membuat dokumen saat render gagal.

## Perintah dan hasil aktual

- `rtk proxy php storage/framework/testing/kkprl-export-diagnose.php`: diagnosis lokal baca saja; Bab 1 complete, rendering Word/PDF di memori berhasil, write private storage/database dan AI nol. Script scratch berada pada direktori testing ignored; tidak menyimpan payload/binary proposal ke disk.
- `rtk proxy php storage/framework/testing/kkprl-cache-mysql-diagnose.php`: raw PNG gagal HY000/1366; Base64 round trip PASS; fixture MySQL dihapus, write database pengguna nol. Script scratch tidak memuat credential literal atau data pribadi.
- RED `rtk proxy php artisan test --compact --filter=satellite_cache_round_trips`: 1 gagal/0 assertions, constraint UTF-8 menolak PNG dengan serializer MySQL Laravel actual; 0,97 detik. Reproduksi fixture ini tidak bergantung pada database pengguna atau jaringan.
- GREEN `rtk proxy php artisan test --compact --filter=KkprlLocationMapTest`: awal 14 lulus/63 assertions (2,82 detik); setelah menambah action Livewire: **15 lulus/72 assertions (4,77 detik)**. Action menghasilkan dua file private, Bab 2/3 tetap incomplete, payload tidak berubah. Encoded cache invalid/oversized, PNG rusak, legacy namespace tidak dihapus dan cache hit identik diuji.
- Pengulangan MySQL fixture actual setelah fix: `actual_provider_mysql_cache=PASS identical PNG`, dua pemanggilan tile/1 request HTTP fake; fixture bersih kembali.
- `rtk proxy php vendor/bin/pint --dirty`: ordered_imports diperbaiki pada tes; `rtk proxy php vendor/bin/pint --dirty --test`: **22 file PASS**.
- `rtk git diff --check -- app/Services/KkprlWorldCoverTiles.php tests/Feature/KkprlLocationMapTest.php docs/kkprl-chapter-one-map-validation.md docs/kkprl-security-review.md`: PASS.
- `rtk proxy php artisan test --compact`: **506 lulus/2942 assertions (128,52 detik)**, berurutan. Regresi Bab 1–5, pasut/grafik, GMRT, dokumen, private download, akun/approval/owner/lock dan revisi tetap lulus; HTTP fake/offline, tanpa Gemini berbayar. Build/browser visual tidak diulang karena tidak ada perubahan UI/layout/binary renderer; action Livewire dan Word/PDF diuji dengan batas cache yang mereproduksi bug.

## Graphify dan batas

Refresh indeks utama enam sumber kode/tes/dokumentasi dilakukan setelah source freeze, dengan audit `graphify-out/map-cache-audit.json`. Arah/parallel/hyperedge, hash sumber/artefak dan endpoint diperiksa; cakupan tetap terbatas. Script scratch, `.env`, data pengguna, binary dan database tidak diindeks. Hosting belum diuji langsung dan network satelit live tidak dipanggil untuk tes otomatis; data asli lokal hanya diperiksa/render baca saja dengan HTTP fake.

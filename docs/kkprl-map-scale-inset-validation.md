# Validasi skala cetak dan inset Bab 1

Tanggal: 12 Oktober 2026. Ruang lingkup mengikuti [spesifikasi](kkprl-map-scale-inset-spec.md) dan [ADR-033](decisions/ADR-033-print-scale-and-location-inset.md). Tidak menggantikan hasil historis validasi fitur lain.

## Hasil implementasi

Peta utama pada ekspor baru adalah grid/geometri asli tanpa raster satelit yang diperbesar. Viewport otomatis memilih denominator minimum 500 dan memperbesar denominator bila seluruh tapak beserta margin tidak muat. Label skala utama dan inset terpisah. Inset memiliki cakupan lebih luas, denominator minimum 50.000 dan WorldCover 2021 sekitar 10 m. Kotak biru merupakan cakupan peta utama; tanda + adalah pusat cakupan, bukan perluasan area permohonan. Nomor titik, legenda, utara, ukuran dua desimal, warning perbedaan dimensi dan atribusi tetap tersedia.

Lebar gambar fisik Word/PDF adalah 700 point. Word memiliki extent 8.890.000 EMU dan PDF memakai transform `[700,0,0,427.778,71,83]`. Denominator dihitung pada tengah frame Web Mercator dan ukuran cetak asli 100%. Skala grafis tetap disediakan. Snapshot baru `2026-10-12-map-inset` berbeda dari versi terdahulu; rekonstruksi `2026-10-12-map` mempertahankan tiga key metadata dan hash tanpa layout inset baru. `2026-10-11` dan null tetap didukung.

Koordinat dan pengukuran sebelum/sesudah viewport/render sama. Tes operasi Livewire menegaskan payload proposal tidak berubah akibat generate. Tidak ada form, persyaratan progres, migration, dependency, layanan atau endpoint baru. Aset aplikasi tidak berubah, sehingga build Vite tidak diulang. Working tree sebelumnya dipertahankan; tidak commit/push/deploy, migrate atau menyentuh database pengguna.

## Perintah dan hasil aktual

PHP lokal Laragon 8.4.8; composer.lock: Laravel 13.26.1, Livewire 4.4.1, Filament 5.7.6, Dompdf 3.1.6. Semua shell memakai awalan RTK.

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| RED viewport | `rtk proxy php artisan test --compact --filter=ProposalLocationMapViewportTest` | 4 gagal, 0 assertions, 0,33 s; class belum tersedia |
| GREEN viewport | Perintah yang sama sesudah implementasi | 4 lulus, 139 assertions, 0,32 s |
| RED renderer/snapshot/ukuran Word | `rtk proxy php artisan test --compact --filter='detail_is_clear\|grid_inset\|word_and_pdf_embed'` | 3 gagal, 5 assertions, 1,55 s; metadata skala belum ada dan lebar Word lama tidak 700 point |
| GREEN peta + viewport | `rtk proxy php artisan test --compact --filter='ProposalLocationMapViewportTest\|KkprlLocationMapTest'` | 21 lulus, 225 assertions, 3,15 s |
| Regresi pertama | `rtk proxy php artisan test --compact` | 512 lulus, 3.095 assertions, 79,86 s |
| Regresi akhir setelah label dan preview disempurnakan | `rtk proxy php artisan test --compact` | 512 lulus, 3.095 assertions, 91,11 s |
| Scope akhir setelah format final dan pemeriksaan terputus dilanjutkan | `rtk proxy php artisan test --compact --filter='ProposalLocationMapViewportTest\|KkprlLocationMapTest'` | 21 lulus, 225 assertions, 55,88 s |
| Format akhir | `rtk proxy php vendor/bin/pint --test` diikuti 8 berkas PHP scope | PASS, 8 berkas; kegagalan style sebelumnya diperbaiki dengan Pint |
| Whitespace | `rtk git diff --check -- app/Services/KkprlProposalSnapshotService.php docs/kkprl-user-and-staff-guide.md docs/kkprl-proposal-builder-spec.md` dan pemeriksaan Python berkas untracked scope | PASS |
| OOXML | `rtk proxy php tools/kkprl-document-schema-smoke.php storage/framework/testing/kkprl-ooxml-schema docs/export_sample/peta/QA` | 24 DOCX × 4 bagian = 96 pemeriksaan PASS, termasuk 18 contoh inset baru dan 6 contoh lama |
| Ukuran PDF/gambar | `rtk proxy node /tmp/kkprl-map-viewer/render-inset.mjs` | 18 PDF PASS; masing-masing 5 halaman, empat portrait 595×842 point, terakhir landscape 842×595; transform gambar lebar 700 point |

PHPUnit dijalankan serial memakai SQLite memory, Storage fake, cache testing dan HTTP/AI fake. Tidak memakai database pemohon, API Gemini atau jaringan provider pada tes otomatis. Cache Base64 text-safe, deadline/redirect/invalid PNG, pending/cross-owner/locked, pencabutan approval atau perubahan raw saat fetch, integritas/private download, snapshot lama, Bab 1–5, pasut/grafik, GMRT, ekspor serta workflow petugas tercakup pada regresi yang benar-benar dijalankan.

## Preview sintetis dan browser

`tools/kkprl-location-map-preview.php` memasang database memory dan menolak seluruh akses DB melalui beforeExecuting. Mode offline memakai `Http::preventStrayRequests()`. Mode `--live` hanya mengambil tile WorldCover publik, cache array dan koordinat demonstrasi; tidak mengambil data pemohon. Contoh sebelumnya dipertahankan dengan prefix baru `inset-` pada `docs/export_sample/peta/QA`.

Perintah preview yang dijalankan: `rtk proxy php tools/kkprl-location-map-preview.php` dengan opsi kosong, `--small-site --long-source`, `--live --small-site`, `--live --small-site --long-source`, `--antimeridian`, dan `--high-latitude`. Tiap perintah membuat PNG/DOCX/PDF untuk polygon, line, point.

- Polygon kecil sekitar 44,5×44,5 m / 0,20 ha: utama 1:1.000; inset 1:50.000. Line sekitar 44,5 m: utama 1:500. Point: utama 1:500.
- Polygon sekitar 334×334 m: utama 1:5.000; inset 1:100.000. Seluruh titik tetap muat.
- Antimeridian 179,9995 sampai −179,9995: polygon utama 1:2.000, line 1:1.000, point 1:500; geometri dan grid tetap lokal melewati 180°, bukan membentang seluruh dunia.
- Latitude 80°: polygon utama 1:2.000, line/point 1:500. Faktor latitude masuk skala; sumber tidak dipindah ke lokasi contoh Indonesia.
- Sumber token `W` sepanjang 300 karakter tetap lengkap, dibungkus dan terpisah dari atribusi provider. Warning dimensi pada fixture batas tetap terbaca. PHP peak yang dilaporkan script 66–88 MiB, bukan jaminan total memori proses atau limit hosting.

Chrome DevTools MCP memakai context terisolasi `kkprl-inset-synthetic-20261012` dan viewer sementara `127.0.0.1:8771` dengan whitelist hanya file sintetis serta library lokal. PDF.js dan docx-preview memuat polygon, line, point tanpa error/warning konsol. Desktop 1440×1100: gambar Word 933,328 CSS pixel, setara 700 point; peta, inset, sumber panjang dan footer berada pada section landscape. Mobile memakai emulasi viewport 390×844: documentWidth=390, canvas PDF sekitar 350 pixel, tanpa overflow halaman; Word tetap ukuran cetak dan dapat digeser di container. Peta cetak yang diperkecil ke layar mobile memerlukan zoom untuk membaca detail; ini bukan UI peta baru pada wizard.

Pemeriksaan visual memakai PNG, hasil render halaman PDF dan screenshot inline browser. Screenshot browser tidak disimpan pada folder proyek karena connector menolak path WSL; tidak ada data pemohon yang dibuka lewat connector. Hasil PDF-render PNG tersimpan di QA. Viewer dihentikan setelah pemeriksaan.

## Review dan batas

Review correctness, readability, architecture, security dan performance memeriksa perubahan viewport, renderer, snapshot, dua format dan tes. Tidak ditemukan temuan blocking. Penyesuaian font angka menjaga label denominator panjang di panel; renderer tetap memproyeksikan titik dari parser existing. Tidak ada URL pengguna, bypass akses, cache overlay private atau penambahan instalasi sistem. Budget jaringan dan checkpoint persistence existing dipertahankan.

Skala adalah perkiraan lokal proyeksi, bukan akurasi pengukuran legal. Mengubah ukuran gambar atau fit-to-page mengubah skala angka. WorldCover merupakan komposit 2021, bukan citra terkini; detail raster tetap terbatas sekitar 10 m. Hosting aktual dan pagination Microsoft Word native belum diuji. docx-preview memeriksa section/gambar, bukan pagination Microsoft Word. Regresi tidak menjamin setiap konfigurasi hosting.

## Graphify

Scope tahap ini adalah 8 berkas PHP dan 6 dokumen Markdown. Graph utama diperbarui dari AST kode serta ekstraksi semantik dokumen, dengan preservasi arah, parallel edges dan metadata di luar scope. Verifikasi source/artifact hash, pencarian simbol viewport/skala/inset/snapshot, endpoint node serta batas ambiguitas tercatat pada [audit skala/inset](../graphify-out/scale-inset-audit.json) dan [GRAPH_REPORT](../graphify-out/GRAPH_REPORT.md). Audit peta/cache sebelumnya tetap historis; query saja tidak dianggap refresh. Indeks utama tetap mencakup kode dan dokumentasi utama KKPRL, bukan seluruh repositori atau sample dokumen private.

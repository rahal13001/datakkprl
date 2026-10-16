# Validasi peta utama OpenStreetMap Bab 1

Tanggal: 12 Oktober 2026. Scope: mengganti frame utama kosong/grid dengan raster OSM sesuai pilihan pengguna, mempertahankan inset WorldCover, geometri dan skala. Mengikuti [spesifikasi](kkprl-osm-main-map-spec.md) dan [ADR-035](decisions/ADR-035-openstreetmap-main-map-with-satellite-inset.md).

## Diagnosis dan perubahan

Gambar laporan pengguna menampilkan inset citra yang berhasil, sedangkan frame utama abu-abu. Graphify utama dan kode renderer diverifikasi: ADR-033 memang memilih grid utama tanpa raster untuk menghindari pembesaran citra 10 m. Ini keputusan penyajian tahap sebelumnya, bukan bukti bahwa semua tile provider gagal. Pengguna memilih OSM utama.

`KkprlOpenStreetMapTiles` menggunakan endpoint HTTPS tetap, identifikasi aplikasi, wrapping longitude, cache publik Base64 minimum tujuh hari/freshness lebih panjang, validasi PNG dan larangan redirect. `ProposalLocationMapViewport::streetZoom()` memilih resolusi maksimum 19/16 tile tanpa mengubah viewport atau skala. Renderer menambahkan OSM di bawah grid/geometri, mempertahankan inset WorldCover dan membagi deadline fetch 20 detik antara keduanya. Atribusi OSM berada di frame utama, terpisah dari sumber pengguna hingga 300 karakter. Config utama/inset dipisah; error provider menghentikan generate secara jelas.

Snapshot baru `2026-10-12-map-osm` menyimpan provider utama/layout. Rekonstruksi semua versi historis memakai metadata provider immutable dokumen. Perubahan konfigurasi tidak membuat hash unduhan lama berubah. Dokumen existing tidak ditimpa. Tidak ada migration, permission, route aplikasi, dependency atau layanan tambahan. Teks bantuan sumber lokasi pada wizard diperbarui.

## Lingkungan dan isolasi

Composer aktual: Laravel 13.26.1 (`^13.0`), Livewire 4.4.1 (`^4.0`), Filament 5.7.6 (`^5.0`). PHP Laragon 8.4.8 dengan GD/FreeType. PHPUnit SQLite memory, private storage fake, cache terisolasi dan HTTP fake/stray-request prevention. Gemini berbayar tidak dipanggil. Preview memakai SQLite memory dengan hook penolakan query database, identitas/koordinat sintetis dan cache file QA terpisah untuk tile publik. Database/proposal/dokumen pengguna tidak diubah. Semua shell memakai `rtk`; tidak commit/push/deploy.

## Perintah dan hasil aktual

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| RED, sebelum implementasi | `rtk proxy php artisan test --compact --filter=KkprlOpenStreetMapTest` | 11 failed, 7 assertions, 18,48 s: provider/method belum ada dan frame masih abu-abu. Satu kegagalan tambahan berasal dari fixture Livewire yang belum memilih proposal; fixture diperbaiki. |
| GREEN awal scope | `rtk proxy php artisan test --compact --filter='KkprlOpenStreetMapTest\|KkprlLocationMapTest\|ProposalLocationMapViewportTest'` | 32 passed, 306 assertions, 14,55 s. |
| Regresi penuh | `rtk proxy php artisan test --compact` | **542 passed, 3.283 assertions, 303,66 s**. Meliputi KKPRL Bab 1–5, akun/approval, private download, penghapusan, revisi, AI fake, pasut/grafik, GMRT fake, ekspor Word/PDF dan workflow petugas. |
| Scope terakhir setelah posisi atribusi dirapikan | Perintah scope di atas | **32 passed, 306 assertions, 10,68 s**. Penyesuaian terakhir hanya posisi atribusi gambar dan opsi preview. |
| Format akhir | `rtk proxy php vendor/bin/pint --test` dengan delapan berkas PHP scope | **PASS, 8 files**. Pint awal memperbaiki satu style import pada tes. |
| Build | `rtk proxy npm run build` | **PASS**, 59 modules, 20,03 s, Vite 7.3.6. |
| Whitespace tracked | `rtk proxy git diff --check` | Exit 0; ada warning normalisasi CRLF existing di luar scope, tidak ada pelanggaran pada diff. Scan seluruh scope kemudian menemukan 51 baris trailing whitespace existing di Blade, di luar baris bantuan yang berubah; tidak dirapikan agar tidak mengganggu pekerjaan sebelumnya. Sumber scope lainnya diperiksa terpisah. |
| Composer/XML | `rtk proxy python3` membaca composer.json/lock dan parse phpunit.xml | Versi di atas terkonfirmasi; XML valid. |
| Aset build | `rtk proxy python3` memeriksa referensi file pada public/build/manifest.json | CSS/JS yang dirujuk ada; missing 0. |

Filter pada tabel memakai `\|` untuk escaping Markdown; perintah shell aktual memakai `|` dalam string filter yang dikutip, tanpa backslash.

Delapan PHP: viewport, provider OSM, renderer peta, snapshot service, config KKPRL, tes OSM/peta existing dan tool preview. Tes baru membuktikan pixel OSM utama/WorldCover inset, geometri sebelum/sesudah identik, cache tanpa request kedua, host/header/wrapping, serializer text MySQL dengan CHECK UTF-8 pada SQLite terisolasi, freshness lebih panjang, respons terpotong/oversize/redirect/503/HTML, deadline/indeks invalid tanpa HTTP, antimeridian/latitude tinggi/batas tile, seluruh hash layout historis, private download sesudah perubahan config, approval dicabut saat fetch tanpa penulisan, pesan Livewire saat provider gagal dan mode operator eksplisit.

## Preview dan format keluaran

- `rtk proxy php tools/kkprl-location-map-preview.php --osm --small-site --long-source`: tiga bentuk menghasilkan PNG/DOCX/PDF dengan OSM **fake**, inset tanpa citra secara eksplisit. Peak PHP memory 70/82/88 MiB. Bukan bukti jaringan provider.
- Dua percobaan awal `--osm --live --small-site` gagal dengan validasi pengambilan WorldCover; tidak dianggap sukses meskipun CLI renderer dapat mengembalikan exit 0 setelah exception. Error generik tidak cukup untuk menyimpulkan penyebab jaringan/deadline. Probe satu tile WorldCover publik berikutnya menghasilkan HTTP 200, image/png, 133.115 byte dalam 3,48 s.
- `rtk proxy php tools/kkprl-location-map-preview.php --osm --live --street-only --small-site`: OSM nyata berhasil untuk tiga bentuk; inset dimatikan eksplisit untuk diagnosis. Tidak dianggap bukti kedua provider.
- **`rtk proxy php tools/kkprl-location-map-preview.php --osm --live --small-site --long-source`: akhirnya berhasil untuk polygon, line dan point dengan OSM nyata dan inset WorldCover nyata.** Cache QA tile publik terisolasi dipertahankan antar percobaan. Peak PHP memory polygon/line/point 72/82/88 MiB; ini bukan jaminan batas memory hosting.
- Preview akhir `rtk proxy php tools/kkprl-location-map-preview.php --osm --live --small-site` juga berhasil untuk tiga bentuk (68/80/86 MiB), memakai cache tile QA yang sama. Contoh tanpa stress sumber panjang: `osm-live-{polygon|line|point}-small-site.{png|docx|pdf}`. PNG polygon diperiksa visual; sumber singkat dan kedua atribusi utuh.
- `rtk proxy python3 /tmp/osm-package-check.py`: **PASS, tiga paket**. PNG 1.800×1.100; halaman map Word landscape 16.838×11.906 twip; gambar 8.890.000×5.432.778 EMU (700 point, rasio tetap). PNG yang tertanam pada Word byte-identik dengan PNG preview. Empat bagian XML setiap paket parse valid; ini bukan ulang validasi XSD penuh.

Contoh fixture terbaru ada di `docs/export_sample/peta/QA/osm-live-{polygon|line|point}-small-site-long-source.{png|docx|pdf}`. Polygon utama 1:1.000, line/point 1:500; inset 1:50.000. Luas 0,20 ha dan panjang 0,04 km dua desimal sesuai fixture, titik diberi nomor dan atribusi/sumber 300 karakter tidak saling menutup. Contoh berisi fixture sintetis, bukan proposal pengguna.

## Browser desktop/mobile

Viewer QA localhost 127.0.0.1:8773 dalam context Chrome terisolasi, tanpa bootstrap aplikasi/database dan hanya mengizinkan nama file contoh sintetis OSM. PDF.js 4.10.38/docx-preview 0.3.3/JSZip 3.10.1 hanya untuk viewer QA, bukan dependency aplikasi/hosting. Inspeksi runtime bersifat read-only.

- Desktop 1.440×1.000: PDF polygon selesai lima halaman (empat portrait 595,28×841,89 point, peta terakhir landscape 841,89×595,28). Canvas 1.262×892; main OSM, batas, inset citra, sumber panjang dan atribusi terlihat utuh. Word preview landscape 841,9×595,3 point, gambar 933,33×570,36 CSS pixel sesuai 700 point; natural size 1.800×1.100. PNG line lengkap. Tidak ada error/warning konsol pada ketiga viewer.
- Emulasi mobile/touch **390×844**: PDF polygon selesai dengan canvas sekitar 357,98×253,03, body 358 pixel; PNG line 358×218,77 dan gambar complete. Tidak ada overflow body. Word tetap merender gambar penuh pada kertas asli, dengan scroll horizontal **di dalam container QA** (358/1.133 pixel), tanpa overflow body; bukan klaim layout Word berubah menjadi halaman mobile. PDF point juga selesai dan memiliki halaman peta landscape. Screenshot PDF desktop/mobile dan Word desktop diperiksa.
- Percobaan awal `resize_page(390,844)` ternyata dibatasi Chrome ke innerWidth 500; bukti mobile yang digunakan adalah emulasi viewport 390, bukan hasil resize tersebut.

Ini pemeriksaan ekspor melalui viewer QA, bukan UAT ulang seluruh wizard/panel. Native Microsoft Word, perangkat fisik dan hosting shared aktual belum diverifikasi. Tidak ada route preview publik atau ekspor peta terpisah untuk pemohon yang ditambahkan pada tahap ini.

Sesudah verifikasi, tiga tab QA ditutup dan server PHP milik sesi pada port 8773 dihentikan. Tab/sesi pengguna lain tidak disentuh. Harness QA berada di storage testing ignored dan tidak masuk scope Graphify atau aplikasi produksi.

## Review, dokumentasi dan Graphify

Review kode menelusuri provider, cache, batas viewport, renderer, snapshot immutable serta checkpoint document service. URL tetap/redirect ditolak, payload dan hak akses tidak berubah, dan dokumen lama/private storage tetap dilindungi. Tidak ada blocker yang ditemukan dalam scope ini. Risiko tersisa: ketersediaan provider publik, cakupan data OSM, pembesaran raster pada skala dekat dan konfigurasi outbound HTTPS/GD/font/cache hosting.

Spesifikasi, guide, field map, security review dan builder spec diperbarui. ADR-035 menggantikan hanya keputusan grid utama pada ADR-033; sejarah dan skala/inset dipertahankan. Graphify utama diperbarui dari scope **20 sumber: 8 PHP, 1 Blade, 1 XML dan 10 Markdown**, bukan hanya query. Statistik, hash sumber, audit parser/semantic, preservasi relasi terarah di luar scope, simbol baru, endpoint dan konsistensi manifest tercatat pada [audit OSM](../graphify-out/osm-main-map-audit.json) dan [laporan utama](../graphify-out/GRAPH_REPORT.md). Indeks tetap mencakup kode utama/fitur KKPRL; bukan seluruh repositori atau binary contoh. Relasi parser ambigu tetap dilaporkan sebagai ambigu, bukan bukti otorisasi atau relasi pasti. Refresh belum dianggap selesai sampai verifier scope pada audit lulus.

Perintah verifikasi Graphify: `rtk proxy /home/rahal/.local/share/uv/tools/graphifyy/bin/python3 /tmp/kkprl-osm-main-map-graphify/verify_scope.py`. Tidak menggunakan API Gemini berbayar atau mengindeks credential, data pengguna, gambar laporan pengguna maupun binary preview.

## Pemakaian

Generate ulang Bab 1 pada draft/revisi terbuka setelah kode diterapkan untuk melihat OSM utama. Arsip lama tetap seperti semula; submitted/locked memakai workflow revisi. Default provider berada pada config backend. Perubahan ini tidak membutuhkan migration database baru. Tidak ada deployment yang dilakukan dalam sesi ini.

# Validasi peta ekspor Bab 1

Tanggal: 12 Oktober 2026. Framework aktual composer.lock: Laravel 13.26.1, Livewire 4.4.1, Filament 5.7.6, Dompdf 3.1.6; runtime Laragon PHP 8.4.8. Working tree dirty/untracked awal dipertahankan. Tidak ada commit/push/deploy atau perubahan database pengguna.

## Implementasi

Ekspor Bab 1 baru memuat peta PNG private pada section/halaman A4 landscape setelah body dan lampiran. Polygon, line dan point berasal dari raw koordinat atau teks editor legacy; grid WGS84, utara, legenda dan skala grafis disertakan. Area/panjang dua desimal mengikuti rumus UI. Sumber pengguna opsional; atribusi citra wajib. Peta jalan Leaflet existing tetap di layar, tanpa menambahkan bulk download tile OSM.

WorldCover RGB 2021 CC BY 4.0 dipakai melalui host/layer WMTS tetap. Render tidak memanggil AI. Request, timeout, pixel, byte dan jumlah titik dibatasi; cache hanya tile publik. Gambar proposal tidak disimpan ke cache/public storage. Snapshot baru mencakup raw dan sumber, versi lama tetap direkonstruksi. Tidak ada migration atau dependency aplikasi baru; `KKPRL_MAP_BASEMAP=none` tersedia secara eksplisit, berlabel tanpa peta dasar, untuk operasi offline.

## Hasil aktual

- RED: `rtk proxy php artisan test --compact --filter=KkprlLocationMapTest`: 6 gagal/5 assertions sebelum fitur, membuktikan peta hilang, sumber tidak memengaruhi snapshot, dan checkpoint belum menerima operasi tile.
- GREEN awal: perintah sama, 6 lulus/27 assertions. Setelah kasus tambahan: 11 lulus/45 assertions (2,81 detik).
- `rtk proxy php artisan test --compact --filter='KkprlLocationMapTest|KkprlApplicantBoundaryTest|KkprlTideEvidenceTest'`: 81 lulus/550 assertions (18,21 detik). Pending, owner, lock, revocation, sumber berubah, polygon silang, antimeridian, polar, JSON rusak, ukuran tile, redirect dan deadline diuji.
- Verifikasi final label lokasi: `rtk proxy php artisan test --compact --filter='KkprlLocationMapTest|KkprlDocumentLayoutTest|KkprlProposalDocumentGenerationTest|KkprlApplicantBoundaryTest'`: 74 lulus/702 assertions (40,58 detik), setelah memakai field kecamatan `subdistrict` aktual.
- Regresi awal: 495 lulus, 2 gagal. Mock Canvas generik belum mendukung CPDF landscape; diperbaiki menggunakan context CPDF asli. Tes lain berbenturan dengan tes peta yang berjalan paralel pada storage fake. Pengulangan terpisah dan suite berurutan menghilangkan benturan; tidak menghapus atau melonggarkan assertion.
- Regresi penuh berurutan awal `rtk proxy php artisan test --compact`: 502 lulus/2915 assertions, 80,08 detik. Database SQLite memory, storage fake dan HTTP fake/offline; tidak memanggil Gemini berbayar. Meliputi Bab 1–5, dokumen, approval/revisi, pasut/grafik dan GMRT.
- Setelah hardening decode pixel, tes baru sempat gagal karena dua `Http::fake` memakai respons pertama. Fixture diganti dengan `Http::fakeSequence`, tanpa melonggarkan assertion. PNG dengan IHDR valid tetapi stream rusak ditolak tanpa cache; entry cache rusak diambil ulang.
- Regresi penuh final `rtk proxy php artisan test --compact`: **503 lulus/2919 assertions, 89,04 detik**. Setelah penataan footer sumber panjang, `rtk proxy php artisan test --compact --filter=KkprlLocationMapTest` dijalankan ulang: **12 lulus/49 assertions, 2,53 detik**.
- `rtk proxy php vendor/bin/pint --dirty --test`: 22 file PASS pada sumber final.
- `rtk proxy node --test tests/js/kkprl-coordinate-measurement.test.mjs tests/js/kkprl-coordinate-manager.test.js`: 2 file tes lulus, 0 gagal, 96,97 ms.
- `rtk proxy php tools/kkprl-location-map-preview.php --long-source`: tiga fixture offline dengan sumber 300 karakter lebar tanpa spasi berhasil, peak memory 66/76/82 MiB. Footer sumber berada di bawah label longitude, tanpa clipping; atribusi provider memakai baris terpisah. Newline sumber dinormalisasi sebagai spasi pada peta, payload asli tidak diubah.
- `rtk proxy npm run build`: PASS, Vite 7.3.6, 59 modules, 7,46 detik. Empat asset tracked yang bersih pada baseline dan dihapus Vite dikembalikan byte persis HEAD; output build baru tetap tersedia. Pekerjaan sebelumnya tidak ditimpa.
- `rtk proxy php tools/kkprl-location-map-preview.php --live`: contoh sintetis polygon/line/point PNG, Word dan PDF berhasil; peak memory kumulatif satu proses 70/80/86 MiB. Database dilarang pada tool. Provider menerima indeks tile demonstrasi publik, bukan identitas/proposal. Dua percobaan preview sempat timeout; ekspor berhenti sesuai desain, tanpa peta kosong. Probe publik PHP mendapat HTTP 200 PNG 256×256, respons level 14 sekitar 2,10/0,82 detik. Ini bukti koneksi lokal, bukan SLA atau tes hosting tujuan.
- `rtk proxy php tools/kkprl-document-schema-smoke.php storage/framework/testing/kkprl-ooxml-schema docs/export_sample/peta/QA`: Awal 12 part OOXML PASS untuk tiga Word; pengulangan pada contoh final dan sumber panjang: 24 part PASS untuk enam Word. Section portrait/landscape dan nomor halaman berlanjut diperiksa.
- PDF.js lokal `rtk proxy node /tmp/kkprl-map-viewer/render.mjs`: masing-masing PDF lima halaman; empat 595×842 pt, terakhir 842×595 pt. Halaman terakhir dirender ke PNG dan diperiksa lokal; geometri, panel, sumber, atribusi dan footer tidak terpotong.
- Browser Chrome pada localhost fixture sintetis: desktop 1440×1000 dan emulasi mobile 390×844. PDF.js dan docx-preview berhasil, console tanpa error/warning, tidak ada overflow root. Fragment input sumber disalin dari Blade aktual dengan CSS build aktual: label terhubung, maxlength 300, helper membungkus dan field berada dalam viewport. Ini pemeriksaan fragment UI/hasil dokumen, bukan pengulangan seluruh alur login/wizard di hosting. docx-preview menampilkan section landscape dengan satu gambar; pagination Word native belum diuji.

Contoh review: `docs/export_sample/peta/QA/satelit-polygon.{png,docx,pdf}`, `satelit-line.*`, `satelit-point.*`; identitas dan geometri merupakan fixture sintetis. Contoh asli `docs/export_sample` dan peta ArcGIS tidak ditimpa, tidak diindeks Graphify atau dikirim ke browser eksternal.

## Graphify dan batas

Pembaruan indeks utama kode+dokumentasi dan audit dilakukan setelah sumber dibekukan. Hasil hash, endpoint, relasi terarah/parallel serta relasi ambigu dicatat dalam `graphify-out/location-map-audit.json` dan `GRAPH_REPORT.md`. Query awal memakai graph utama sebagai peta kemudian kode diverifikasi langsung; query bukan pembaruan indeks.

Hosting belum diuji langsung. Periksa PHP GD/FreeType, font vendor, HTTPS ke WMTS, cache Laravel existing, private storage writable, memory dan waktu request sebelum memakai di produksi. Tidak perlu ArcGIS, GDAL, browser runtime atau layanan baru. Citra sekitar 10 m dapat terlihat berpiksel pada tapak kecil; pembesaran tidak menambah detail.

## Koreksi cache MySQL pada Laragon

Pengujian awal cache array tidak mencakup kolom UTF-8 pada database cache MySQL. Bug PNG biner/error 1366 direproduksi dan diperbaiki dengan encoding Base64 namespace baru; tidak mengubah tabel, `.env`, dokumen lama atau database pengguna. Hasil RED/GREEN, verifikasi MySQL fixture dan regresi lanjut tersedia pada [validasi cache](kkprl-chapter-one-map-cache-validation.md) dan [ADR-032](decisions/ADR-032-text-safe-map-tile-cache.md). Ini melengkapi hasil historis di atas, bukan mengklaim konfigurasi MySQL sudah diuji saat validasi awal.

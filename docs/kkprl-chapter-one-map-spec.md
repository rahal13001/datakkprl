# Peta lokasi otomatis pada ekspor Bab 1

Tanggal: 12 Oktober 2026. User menyetujui implementasi halaman A4 landscape khusus, citra gratis, dan batas shared hosting. Ini satu kemampuan ekspor; pekerjaan akun dan tata letak sebelumnya dipertahankan.

Penyajian terbaru mengikuti [spesifikasi OSM](kkprl-osm-main-map-spec.md) dan [ADR-035](decisions/ADR-035-openstreetmap-main-map-with-satellite-inset.md): peta utama OpenStreetMap, grid/geometri asli dan skala angka mulai 1:500; inset WorldCover paling dekat 1:50.000 tetap ada. Snapshot `2026-10-12-map-osm` mempertahankan versi map/inset terdahulu. Tahap grid utama pada [ADR-033](decisions/ADR-033-print-scale-and-location-inset.md) dan implementasi awal di bawah dipertahankan sebagai sejarah. Koordinat/luas tidak berubah; kontrol provider, akses dan private storage tetap berlaku.

## Tujuan dan batas

Ekspor baru Word/PDF Bab 1 menambahkan satu halaman peta setelah isi dan lampiran bab. Peta memuat geometri koordinat tersimpan, nomor titik, grid WGS84, panah utara, legenda, skala grafis, identitas lokasi, serta luas hektar/panjang kilometer dua desimal. Angka bersifat informasi geometri bola bumi, bukan pengukuran survei legal. Isian dimensi tidak ditimpa. Perbedaan dimensi dicantumkan.

Peta dasar awal adalah komposit RGB Sentinel-2 ESA WorldCover 2021, sekitar 10 m, CC BY 4.0 melalui WMTS publik Terrascope. Tahun dan atribusi wajib tercantum. Tidak mengklaim citra terkini atau resolusi survei. Tidak ada logo wajib, overlay garis pantai, API berbayar, GIS, browser runtime, layanan tambahan, atau dependency aplikasi baru. Peta jalan Leaflet existing tetap pada layar; pengunduhan massal tile OSM untuk ekspor tidak ditambahkan.

Sumber data lokasi dari pengguna bersifat opsional, teks maksimal 300 karakter; kosong tidak menampilkan baris sumber pengguna. Atribusi provider tidak dapat dihapus. Tidak menambah persyaratan penyelesaian Bab 1.

## Arsitektur, keamanan, dan migrasi

PHP GD membuat PNG di memori. Tile publik dicache menggunakan cache Laravel existing; tidak ada identitas, nomor tiket, geometri overlay, atau PNG proposal dalam cache publik. Endpoint HTTPS, layer, waktu dan format tetap, tanpa URL pengguna atau redirect. Maksimum 16 tile, 20 detik total, 6 detik per request (connect maksimal 4 detik), 512 KiB per tile, 256×256 pixel; zoom maksimal 14. Tile invalid/timeout/di luar cakupan menghasilkan error ekspor yang dapat dicoba ulang, bukan citra palsu. Operator dapat memilih KKPRL_MAP_BASEMAP=none secara eksplisit untuk peta grid tanpa citra; labelnya menyatakan tanpa peta dasar. PHPUnit memakai mode ini untuk regresi offline, sedangkan tes WMTS menggunakan HTTP fake. Koordinat tidak dikirim sebagai payload ke AI; provider hanya menerima indeks tile wilayah. Longitude melintasi antimeridian harus tetap berurutan lokal. Latitude di luar cakupan Mercator ditolak jelas.

Snapshot layout baru `2026-10-12-map` menyertakan koordinat raw dan sumber opsional dalam metadata terenkripsi terpisah. Versi `2026-10-11` dan tanpa versi tetap direkonstruksi dengan hash historis. Tidak ada migration database: menggunakan payload encrypted dan metadata snapshot existing. Dokumen lama tidak ditimpa dan tidak mendapat peta secara otomatis. Draft lengkap dapat digenerate ulang; locked/submitted tetap mengikuti revisi.

| Pelaku | Membaca dokumen | Generate peta/dokumen |
|---|---|---|
| Pemohon pending | Dokumen generated miliknya yang valid | Ditolak sebelum request tile |
| Pemohon approved | Dokumen miliknya yang valid | Draft miliknya, lengkap, unlocked; checkpoint ulang setelah operasi jaringan |
| Akun lain | Ditolak | Ditolak |
| Petugas | Permission review/download existing | Tidak memperoleh kewenangan baru |

Approval, owner, lock, snapshot dan manifest diperiksa kembali sebelum penyimpanan. PNG hanya tertanam pada dokumen private, tidak diberi endpoint publik atau akses lampiran tambahan. Dokumen memuat peta yang sama untuk kedua format dalam satu operasi generate. Cache publik dapat bertahan meskipun proposal ditolak pada checkpoint; tidak berisi data proposal.

## Implementasi dan verifikasi

Urutan: tes merah geometri/render/snapshot/otorisasi; geometri dan WMTS terbatas; renderer GD; section Word dan halaman landscape PDF melalui adapter CPDF existing; sumber opsional; regresi dan contoh sintetis; dokumentasi, ADR-031 dan pembaruan Graphify kode+dokumen.

Kode pada `app/Domain/Kkprl` dan `app/Services`, fixture terisolasi di `tests`, dokumentasi pada `docs`. Ikuti PHP typed methods dan Pint, misalnya `public function render(array $values): array`. Tidak mengubah vendor atau migration lama. Jangan commit/push/deploy.

Perintah: `rtk proxy php artisan test --compact --filter=KkprlLocationMap`; `rtk proxy php artisan test --compact`; `rtk proxy php vendor/bin/pint --dirty`; `rtk proxy npm run build`; `rtk git diff --check`. PHPUnit SQLite memory, HTTP fake, storage/cache terisolasi; tidak memakai Gemini atau API berbayar. Schema OOXML dan pemeriksaan PDF/browser memakai fixture sintetis. Kebutuhan hosting tetap PHP GD, HTTPS keluar, dan memory/time yang memadai; hosting aktual belum dapat diverifikasi lokal.

## Acceptance criteria

1. Polygon/line/point memakai koordinat sebenarnya, nomor titik, grid, utara, legenda dan skala. Luas/panjang sesuai rumus UI; polygon silang tidak menghasilkan luas menyesatkan.
2. PNG tertanam pada Word dan PDF, dengan halaman fisik A4 landscape khusus; isi bab tetap portrait sebagaimana sebelumnya dan nomor halaman berlanjut.
3. Tahun, resolusi dan atribusi citra terlihat; sumber opsional kosong hilang. Dimensi dua desimal dan warning jika berbeda.
4. Endpoint tetap; anggaran jaringan/memory, ukuran/content tile serta cache dibuktikan dengan fake. Tidak ada request otomatis dari tests ke Internet.
5. Retry idempotent, snapshot raw/sumber berubah menandai dokumen stale; versi lama dapat diunduh tanpa perubahan file atau hash.
6. Pending/cross-owner/locked ditolak sebelum tile. Approval/owner/koordinat yang berubah saat fetch tidak dapat menyimpan dokumen.
7. Regresi builder Bab 1–5, pasut/grafik, GMRT, ekspor dan workflow petugas lulus. Perintah/hasil aktual, pemeriksaan contoh dan keterbatasan dicatat di laporan validasi.

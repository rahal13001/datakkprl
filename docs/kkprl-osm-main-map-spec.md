# Peta dasar OpenStreetMap pada ekspor Bab 1

Tanggal: 12 Oktober 2026. Pemohon melaporkan peta utama tanpa gambar dasar pada `docs/sampel_peta/sc_peta.jpg`. Audit menemukan bahwa ADR-033 sengaja menempatkan citra hanya pada inset. Pengguna memilih peta jalan OpenStreetMap pada peta utama; inset WorldCover dan geometri permohonan tetap dipertahankan.

## Perilaku dan acceptance criteria

- Peta utama ekspor baru memakai raster jalan OpenStreetMap sebelum grid/geometri. Nomor titik, ukuran ha/km dua desimal, warning dimensi, skala angka/grafis, utara, sumber opsional dan landscape tetap ada. Luas, koordinat, payload dan hitungan kelengkapan tidak berubah.
- Skala detail existing tetap mulai 1:500 dan otomatis memuat seluruh titik. Zoom tile OSM dipilih sesuai frame, maksimum 19 dan paling banyak 16 tile per frame. Inset WorldCover tetap maksimum zoom 14, skala paling dekat 1:50.000. Kedua frame berbagi satu deadline fetch 20 detik, maksimum 32 tile total; Word/PDF berbagi gambar dan cache.
- Provider fixed HTTPS dengan indeks integer; tanpa URL pengguna, redirect, prefetch wilayah/zoom lain atau ekspor arsip tile offline. Hanya viewport keluaran yang pengguna minta digenerate. Batas PNG 512 KiB/256×256 dan validasi decode penuh berlaku; cache Base64 aman untuk text MySQL.
- Request OSM memakai User-Agent aplikasi dan Referer origin aplikasi. Cache sekurangnya tujuh hari, diperpanjang bila header freshness provider lebih lama. Atribusi OSM/ODbL beserta URL copyright terlihat pada gambar; atribusi WorldCover tetap ada jika inset aktif.
- Kegagalan provider menghentikan generate dengan pesan validasi, tidak menghasilkan peta dasar kosong diam-diam. Operator dapat memilih `KKPRL_MAP_MAIN_BASEMAP=none` untuk grid utama; `KKPRL_MAP_BASEMAP=none` tetap mengendalikan inset. Ini konfigurasi operator, bukan input pemohon.
- Snapshot baru `2026-10-12-map-osm` menyimpan `layout=osm-detail-inset-v2` dan `main_basemap`. Layout null, `2026-10-11`, `2026-10-12-map` dan `2026-10-12-map-inset` tetap direkonstruksi dengan metadata historis. Metadata provider dokumen existing dibaca dari snapshot immutable, sehingga perubahan konfigurasi tidak membatalkan unduhan lama. Artefak lama tidak ditimpa/dihapus.

## Matriks akses dan transisi

Pending hanya unduh hasil valid miliknya; generate ditolak. Approved hanya generate proposal miliknya, bab lengkap dan unlocked, dengan checkpoint owner/approval/session/hash setelah fetch. Locked/final tetap memakai arsip/revisi existing. Petugas tetap memakai permission download existing; tidak ada kewenangan baru. Cache hanya berisi tile publik, tanpa identitas, tiket, sumber pemohon atau payload. Tidak ada migration database, instalasi sistem, dependency, layanan atau API berbayar baru.

## Implementasi bertahap

1. Provider/viewport: tes merah untuk jalan pada frame utama, cache/header/timeout/invalid PNG, batas zoom/tile dan antimeridian. Tambah provider PHP GD/HTTP dan pilihan zoom tanpa mengubah viewport.
2. Renderer/snapshot: raster OSM utama + WorldCover inset, deadline bersama, atribusi, versi layout dan rekonstruksi historis. Tes format Word/PDF/private dan pencabutan approval selama request OSM.
3. Preview dan verifikasi: fixture sintetis tanpa DB pengguna, PNG/DOCX/PDF dan browser desktop/mobile; regresi serial, Pint, build bila teks UI berubah, dokumentasi/ADR-035 serta Graphify utama kode+dokumen.

## Struktur, style dan perintah

Service di `app/Services`, viewport di `app/Domain/Kkprl`, snapshot existing, config operator di `config/kkprl.php`, tes Feature/Unit, preview `tools/kkprl-location-map-preview.php`. Ikuti PHP typed signatures/Pint dan Blade escaping; provider menerima `tile(int $zoom, int $column, int $row, float $deadline): string`, bukan URL.

- Tes scope: `rtk proxy php artisan test --compact --filter='KkprlOpenStreetMapTest|KkprlLocationMapTest|ProposalLocationMapViewportTest'`.
- Regresi: `rtk proxy php artisan test --compact`, SQLite memory, Storage/cache/HTTP fake, tanpa Gemini berbayar.
- Format: `rtk proxy php vendor/bin/pint --test` diikuti berkas PHP scope.
- Build: `rtk proxy npm run build`.
- Preview: `rtk proxy php tools/kkprl-location-map-preview.php --osm --small-site` (fake); `--osm --live --small-site` hanya tile publik pada koordinat sintetis. `--long-source` memeriksa pembungkusan sumber opsional; `--street-only` menonaktifkan inset secara eksplisit untuk diagnosis preview, bukan fallback produksi.
- Whitespace: `rtk git diff --check` dan pemeriksaan berkas baru.

Working tree existing dipertahankan. Tidak commit/push/deploy, migrate:fresh atau mengubah data pengguna. Detail OSM bergantung kelengkapan data wilayah dan bukan survei. Hosting aktual belum divalidasi. Tile publik bersifat best-effort dan bukan layanan ber-SLA.

Referensi resmi: [OSMF tile policy](https://operations.osmfoundation.org/policies/tiles/), [attribution/copyright](https://www.openstreetmap.org/copyright), [WorldCover annual composites](https://esa-worldcover.org/en/data-access). Hasil aktual dicatat pada `kkprl-osm-main-map-validation.md`.

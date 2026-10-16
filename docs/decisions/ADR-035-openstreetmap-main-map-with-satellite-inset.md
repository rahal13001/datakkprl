# ADR-035: Peta utama OpenStreetMap dengan inset citra

## Status

Accepted. Menggantikan keputusan latar utama tanpa peta dasar pada ADR-033. Skala, inset, geometri, ukuran cetak, private storage dan checkpoint pada ADR-031/032/033 tetap berlaku; sejarah keputusan tersebut dipertahankan.

## Date

2026-10-12

## Context

Pengguna melaporkan frame utama tampak kosong, sementara citra inset tersedia. Penyebabnya keputusan ADR-033 untuk menghindari pembesaran citra WorldCover sekitar 10 m pada tapak kecil. Pengguna memilih OpenStreetMap pada peta utama. Shared hosting tidak dapat memasang GIS/browser runtime atau menambah layanan berbayar. Batas permohonan tidak boleh berubah.

## Decision

- PHP GD merender tile raster OpenStreetMap pada frame utama, lalu grid dan geometri asli. Inset tetap WorldCover 2021. Tidak mengubah titik, luas/panjang, payload, field wajib, progres atau skala cetak. Pembesaran hanya tampilan di kertas.
- Provider OSM memakai host HTTPS tetap `tile.openstreetmap.org`, indeks integer dan wrapping longitude. Zoom maksimum 19 dengan paling banyak 16 tile pada frame utama; inset maksimum 14/16 tile. Kedua frame berbagi deadline network 20 detik, request maksimum 6 detik/connect 4 detik. Tidak ada prefetch, bulk download atau arsip tile offline.
- User-Agent mengidentifikasi aplikasi dan Referer memakai origin `APP_URL`, tanpa path, identitas pemohon, kredensial atau token. Tidak menerima URL pemohon atau mengikuti redirect. Respons maksimum 512 KiB, PNG 256×256 dengan decode GD penuh. Cache Base64 hanya berisi tile publik; aman untuk penyimpanan text MySQL. TTL minimum tujuh hari dan menghormati freshness yang lebih panjang dari provider.
- Atribusi OpenStreetMap contributors, ODbL dan URL copyright dicetak di frame utama. Atribusi/tahun/resolusi WorldCover tetap ada ketika inset aktif. Sumber koordinat opsional pengguna tetap terpisah dan boleh kosong.
- `KKPRL_MAP_MAIN_BASEMAP` default `openstreetmap`; mode `none` hanya pilihan operator untuk grid utama. `KKPRL_MAP_BASEMAP` tetap mengatur inset. Kegagalan provider menghentikan generate dengan validasi; tidak menghasilkan latar kosong diam-diam.
- Snapshot baru `2026-10-12-map-osm` menyimpan `layout=osm-detail-inset-v2` dan `main_basemap`. Null, `2026-10-11`, `2026-10-12-map` dan `2026-10-12-map-inset` tetap didukung. Rekonstruksi hash memakai metadata provider immutable dokumen existing agar perubahan konfigurasi tidak membatalkan unduhan lama. File lama tidak diubah; pemohon dapat generate ulang hanya pada draft/revisi terbuka.
- Akses approved/owner/unlocked, kuota dan checkpoint sesudah operasi network tetap berlaku. Pending tetap hanya wadah dokumen hasil miliknya. Tidak ada migration, permission, route aplikasi, dependency atau instalasi sistem baru.

## Alternatives Considered

- Memperbesar WorldCover pada frame utama: piksel 10 m tetap membesar, tanpa informasi tambahan.
- Mempertahankan grid saja: batas terbaca, tetapi tidak memenuhi pilihan pengguna untuk konteks jalan.
- Rendering vector dengan layanan GIS tambahan: belum diperlukan dan tidak sesuai batas shared hosting.

## Consequences

Peta utama memiliki konteks jalan/bangunan sesuai cakupan OSM. Tile raster masih dapat terlihat membesar pada skala sangat dekat dan data wilayah dapat kurang lengkap; keluaran bukan survei legal atau jaminan detail bangunan. Tile publik bersifat best-effort, tanpa SLA. Hosting aktual dan pagination Microsoft Word native belum diverifikasi; preview lokal menggunakan GD, PDF.js, docx-preview dan pemeriksaan OOXML.

Lihat [spesifikasi](../kkprl-osm-main-map-spec.md) dan [validasi](../kkprl-osm-main-map-validation.md). Referensi resmi: [OSMF tile policy](https://operations.osmfoundation.org/policies/tiles/), [OSM copyright/attribution](https://www.openstreetmap.org/copyright), [OSMF printed works FAQ](https://osmfoundation.org/wiki/Licence/Licence_and_Legal_FAQ), [WorldCover data access](https://esa-worldcover.org/en/data-access).

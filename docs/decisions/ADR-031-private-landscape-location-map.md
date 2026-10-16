# ADR-031: Peta lokasi private pada halaman landscape Bab 1

## Status

Accepted. Memperluas ADR-010, ADR-014 dan ADR-030. Tidak mengganti workflow approval, revisi atau private storage.

## Date

2026-10-12

## Context

Bab 1 menyimpan koordinat dan menampilkan Leaflet, tetapi ekspor hanya berisi teks koordinat. Pengguna meminta peta yang dapat dibaca pada halaman landscape, citra gratis, sumber opsional dan tanpa overlay garis pantai. Shared hosting tidak dapat memasang program sistem. Nomor akun/approval tidak boleh mengubah integritas dokumen lama.

## Decision

- `ProposalLocationGeometry` membaca JSON raw WGS84 atau format teks editor legacy, membatasi 1.000 titik/1 MiB raw, mengurai longitude antimeridian dan menolak polygon silang serta lokasi di luar cakupan Mercator. Luas geometri bola bumi memakai radius 6.371.008,8 m dan panjang haversine; satuan/dua desimal sesuai UI. Ukuran informasional, bukan survei legal, dan tidak menimpa isian dimensi.
- `KkprlWorldCoverTiles` mengambil WMTS publik dari host HTTPS tetap `wmts.terrascope.be`, layer RGB `esa-worldcover-s2rgbnir-10m-2021-v2_tcc`, waktu `2021-01-01`, matrix `EPSG:3857`. Tidak menerima URL pengguna, tidak mengikuti redirect atau memakai token berbayar. Batas 16 tile per peta, 20 detik total, 6 detik/request, connect 4 detik, zoom maksimal 14, PNG 256×256 dan maksimal 512 KiB/tile. Data provider diperiksa sebelum dipakai; cache Laravel existing menyimpan hanya tile publik selama tujuh hari.
- Komposit Sentinel-2 WorldCover 2021 sekitar 10 m dipilih karena lisensi CC BY 4.0 mendukung pemakaian umum dan tidak memerlukan akun. Tahun, resolusi dan kredit ESA/Copernicus/Terrascope selalu tampil. Pembesaran raster tidak menambah resolusi atau mengklaim citra terkini. Tidak mengambil layer klasifikasi tutupan lahan sebagai citra RGB.
- `KkprlLocationMapRenderer` memakai PHP GD dan font DejaVu yang sudah dibundel Dompdf. PNG 1800×1100 dibuat di memori: geometri, nomor titik, grid, panel lokasi, ukuran, warning perbedaan dimensi, legenda, utara dan skala grafis pada tengah peta. Tidak ada logo wajib, data coastline, AI atau GIS runtime baru. Identitas panjang diringkas pada panel; identitas penuh tetap di body dokumen.
- `map_source` adalah teks opsional maksimal 300 karakter dalam payload encrypted; sanitizer dan konversi payload flat/nested mempertahankannya. Tidak masuk katalog field wajib atau progres. Metadata snapshot versi `2026-10-12-map` menambahkan raw koordinat, sumber dan pilihan basemap. Versi `2026-10-11` serta tanpa versi direkonstruksi persis seperti sebelumnya; binary/row historis tidak ditimpa.
- Word menambahkan section landscape setelah isi dan lampiran, dengan nomor halaman berlanjut. PDF memakai adapter CPDF existing untuk menambahkan halaman fisik A4 landscape; akses struktur public CPDF `objects/onPage/mediaBox` diisolasi pada satu method dan diuji ukuran page melalui parser serta PDF.js. CSS named pages Dompdf tidak digunakan. Tidak mengubah vendor atau memasang PDF merger.
- `KkprlProposalDocumentService` memakai satu PNG untuk kedua format dalam satu generate, setelah checkpoint awal. Checkpoint row lock, owner, approval, status dan kedua hash existing tetap dijalankan sebelum penyimpanan. PNG hanya tertanam pada dokumen private; tidak ada route gambar proposal publik atau akses lampiran tambahan.
- Kegagalan citra menghentikan ekspor dengan error yang dapat dicoba ulang; tidak ada fallback diam-diam. Operator boleh memilih `KKPRL_MAP_BASEMAP=none` secara eksplisit untuk peta grid berlabel tanpa peta dasar. PHPUnit memakai mode offline ini; tes provider memilih WorldCover dengan HTTP fake. Preview live hanya memakai fixture sintetis dengan koordinat demonstrasi publik, tanpa database atau Gemini.

## Alternatives Considered

- GIS/converter/browser di hosting: membutuhkan instalasi yang tidak tersedia.
- Mengunduh massal tile OSM untuk ekspor offline: tidak sesuai batas layanan tile standar. Peta jalan layar tetap existing; opsi ekspor jalan ditunda hingga provider/policy yang sesuai tersedia.
- EOX terbaru sebagai default: lisensi nonkomersial tidak cocok untuk seluruh pemohon usaha.
- Menampilkan peta kosong saat jaringan gagal: menyamarkan kegagalan sumber dan menghasilkan dokumen menyesatkan.
- Memasukkan raw koordinat ke snapshot historis: mengubah hash dan menolak dokumen lama yang sebelumnya valid.

## Consequences

Tidak ada migration database atau dependency aplikasi baru. Ekspor satelit membutuhkan GD/FreeType, font vendor, HTTPS keluar dan memory/time yang memadai. PHP lokal telah menghasilkan contoh live; hosting tujuan belum diuji. Tidak ada jaminan SLA provider gratis. Shared hosting dengan limit rendah dapat memilih peta tanpa citra secara eksplisit; tidak ada biaya otomatis.

Dokumen lama tetap dapat dibaca berdasarkan versi dan integritasnya. Generate ulang hanya pada draft lengkap/unlocked atau melalui revisi. Koordinat yang belum dapat dipetakan menghasilkan error jelas; sistem tidak menciptakan lokasi. Word native belum tersedia untuk pemeriksaan pagination; docx-preview memeriksa struktur dan gambar, bukan pagination Microsoft Word.

Referensi: [WorldCover data access dan lisensi](https://esa-worldcover.org/en/data-access), [WMTS Terrascope](https://docs.terrascope.be/Developers/WebServices/OGC/WMTSv2.html), [CPDF Dompdf 3.1.6](https://github.com/dompdf/dompdf/blob/v3.1.6/src/Adapter/CPDF.php), [kebijakan tile OSM](https://operations.osmfoundation.org/policies/tiles/). [Spesifikasi](../kkprl-chapter-one-map-spec.md) dan [validasi](../kkprl-chapter-one-map-validation.md).

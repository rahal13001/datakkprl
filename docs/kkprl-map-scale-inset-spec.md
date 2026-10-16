# Skala cetak dan inset lokasi Bab 1

Tanggal: 12 Oktober 2026. Melanjutkan spesifikasi peta Bab 1 dan ADR-031/032. Pengguna menyetujui inset dan meminta skala angka; area permohonan wajib tetap persis sesuai koordinat.

Pembaruan berikutnya: [spesifikasi OSM](kkprl-osm-main-map-spec.md) dan [ADR-035](decisions/ADR-035-openstreetmap-main-map-with-satellite-inset.md) mengganti latar sederhana utama dengan OpenStreetMap, mempertahankan skala dan inset di bawah. Uraian tahap awal tetap dicatat sebagai sejarah; konfigurasi terbaru memisahkan provider utama dan inset.

## Perilaku yang dituju

Peta utama memakai grid WGS84 dan geometri asli pada latar sederhana. Pembesaran berarti perubahan jarak di kertas, bukan buffer, pergeseran titik, perubahan luas, atau perluasan permohonan. Tidak ada perubahan payload, progres, validasi bab, atau formulir. Polygon, line, dan point tetap memakai parser dan ukuran existing.

Skala utama dipilih otomatis dari 1:500, 1:1.000, 1:2.000, 1:2.500, 1:5.000 dan kelipatan berikutnya. Denominator terkecil adalah 500; denominator bertambah bila tapak beserta margin tidak muat. Ini pilihan tampilan proyek, bukan ketentuan hukum atau klaim akurasi survei. Point memakai 1:500. Peta besar tetap mencakup seluruh koordinat.

Skala dihitung dari lebar fisik gambar 700 point (246,94 mm) dan lebar frame utama 1.190 dari 1.800 piksel (163,26 mm). Label angka menyebut skala di tengah peta, WGS84/Web Mercator, dan berlaku pada ukuran cetak asli/100%. Word dan PDF memakai lebar fisik identik. Memperbesar screenshot atau mengubah ukuran gambar Word mengubah skala angka; skala grafis tetap ikut berubah proporsional. Distorsi proyeksi dan ketelitian koordinat tetap berlaku.

Inset memakai cakupan lebih luas, minimum denominator 50.000, serta minimal empat kali cakupan utama. WorldCover 2021 sekitar 10 m digunakan hanya pada inset; citra tidak diperbesar sampai skala detail. Label tahun, resolusi, atribusi dan sumber opsional dipertahankan. Kotak inset menunjukkan cakupan peta utama, bukan batas permohonan. Geometri asli tetap digambar; penanda pusat membantu menemukan tapak yang lebih kecil dari satu piksel. Mode `none` tidak mengambil tile dan menyatakan inset tanpa peta dasar.

## Batas akses dan migrasi

| Pelaku | Unduh dokumen | Generate |
|---|---|---|
| Pending | Generated miliknya, integritas valid | Ditolak |
| Approved | Generated miliknya, integritas valid | Bab lengkap, miliknya, unlocked dan kuota existing |
| Akun lain | Ditolak | Ditolak |
| Petugas | Permission existing | Tidak ada kewenangan baru |

Checkpoint owner/approval/lock/hash setelah fetch tetap berlaku. Provider tetap fixed HTTPS, cache Base64, maksimum 16 tile dan anggaran 20 detik. Tidak menambah layanan, dependency, program sistem, migration database, atau endpoint gambar. Snapshot layout baru `2026-10-12-map-inset`; versi `2026-10-12-map`, `2026-10-11` dan null tetap direkonstruksi tanpa metadata baru. File lama tetap private dan hash historis dipertahankan. Generate baru pada draft/revisi mengikuti aturan existing; tidak menimpa dokumen lama.

## Tahap dan acceptance criteria

1. Tes merah lalu implementasi viewport/skala: skala cocok ukuran cetak, latitude, antimeridian, batas terbesar 1:500, seluruh titik masuk margin, inset lebih luas dan minimal 1:50.000. Koordinat dan ukuran geometri sebelum/sesudah identik.
2. Renderer dan format: utama tanpa raster pecah, inset WorldCover/offline, label skala utama/inset, skala grafis, legenda, utara, ukuran dan warning terbaca. Word/PDF lebar gambar sama. Snapshot historis dan akses tetap lulus.
3. Preview sintetis kecil/panjang/titik, sumber panjang, antimeridian dan latitude tinggi; inspeksi PNG, OOXML, PDF serta browser desktop/mobile. Tidak memakai data pemohon atau Gemini. Regresi serial, Pint, dokumentasi/ADR dan Graphify utama kode+dokumen.

Perintah/hasil aktual ditulis pada `docs/kkprl-map-scale-inset-validation.md`. Build hanya diperlukan bila aset/UI aplikasi berubah. Tidak commit/push/deploy.

Referensi: [pengertian skala USGS](https://pubs.usgs.gov/fs/2002/0015/report.pdf), [WorldCover annual composites 10 m](https://esa-worldcover.org/en/data-access).

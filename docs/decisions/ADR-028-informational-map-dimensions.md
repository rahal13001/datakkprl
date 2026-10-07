# ADR-028 — Ukuran peta sebagai pembanding dimensi Bab 1

- Status: Accepted
- Tanggal: 2026-10-08
- Melengkapi ADR-010 dan ADR-027; tidak mengganti aturan koordinat, kelengkapan atau penyimpanan proposal.

## Konteks

Editor Bab 1 sudah menerima koordinat DD/DMS, klik peta dan drag marker. Pengguna mengisi dimensi terpisah, tetapi belum dapat melihat perkiraan luas/panjang geometri atau mengetahui perbedaannya dengan isian.

## Keputusan

Modul `coordinateMeasurement` menghitung perkiraan dari daftar koordinat WGS84 di browser. Polygon satu ring memakai integral luas spherical ring; line memakai jumlah jarak haversine seluruh segmen. Radius rata-rata bumi 6.371.008,8 m. Tampilan menggunakan hektar atau kilometer dengan dua desimal dan format Indonesia. Ring tertutup/terbalik menghasilkan ukuran yang sama; selisih bujur pendek menangani antimeridian.

Getter `coordinateManager.measurement` membaca points, shapeType, water_area dan water_area_unit secara reaktif. Ia tidak menulis state atau mengirim request. Semua input manual/drawing memakai daftar titik yang sama. Koordinat parsial/tidak valid dan polygon tanpa luas/bersilangan tidak menampilkan angka stale. Point tidak menampilkan panel ukuran.

Dimensi dibandingkan hanya dengan satuan yang cocok: Hektar (Ha) untuk polygon, Kilometer (Km) untuk line. Jika kedua angka berbeda setelah pembulatan dua desimal, warning menyebut nilai peta dan isian. Satuan berbeda diberi petunjuk; isian kosong tidak dianggap nol. Angka 0 tetap dibandingkan. Pemicu berbasis pembulatan adalah ketelitian tampilan, bukan toleransi survei yang ditetapkan tanpa kebutuhan bisnis.

Peringatan tidak memblokir penyimpanan/export/submit dan tidak mengganti dimensi pengguna. Ukuran computed tidak masuk payload, snapshot atau dokumen. Dengan demikian pembaruan metadata/form tidak mengubah identitas dokumen lama. Approval, ownership, validasi koordinat backend, lock/revisi dan quota tetap berlaku.

## Alternatif

- Menyalin hasil peta otomatis ke kolom dimensi: mengubah data pengguna dan menganggap gambar peta sebagai sumber final. Tidak sesuai kebutuhan warning/pembanding.
- Membandingkan nilai mentah tanpa pembulatan: menghasilkan warning ketika dua angka yang terlihat sama berbeda pada pecahan kecil. Ketelitian dua desimal dipakai untuk keduanya.
- Menambah Turf/layanan GIS/server geodesi: tidak diperlukan untuk informasi satu ring/line lokal. Modul murni cukup dan tidak menambah package, instalasi sistem atau layanan shared hosting.

## Batas dan konsekuensi

Hasil adalah perkiraan model bumi bulat, bukan luas legal, proyeksi survei atau perhitungan ellipsoid penuh. Polygon berlubang/multipolygon tidak didukung editor. Deteksi batas bersilangan memakai segmen pada bujur yang diunwrap; tidak ditujukan untuk polygon global/polar yang melingkari bumi. Dua desimal tidak berarti akurasi survei dua desimal. Informasi frontend dapat dimanipulasi sehingga tidak menjadi bukti regulasi atau batas keamanan.

Lihat [spesifikasi](../kkprl-map-measurement-spec.md) dan [validasi](../kkprl-map-measurement-validation.md). Rumus diperiksa terhadap [sumber area Turf](https://github.com/Turfjs/turf/blob/master/packages/turf-area/index.ts) dan [distance Turf](https://github.com/Turfjs/turf/blob/master/packages/turf-distance/index.ts); library tidak dipasang.

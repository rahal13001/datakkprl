# Ukuran geometri peta Bab 1

Tanggal: 2026-10-08. Permintaan: informasi ukuran polygon/line dari koordinat manual atau gambar peta, serta peringatan jika berbeda dari dimensi yang diisi pengguna.

## Perilaku dan acceptance criteria

- Polygon dengan minimal tiga titik valid menampilkan **Perkiraan luas peta** dalam hektar, dua angka desimal. Line dengan minimal dua titik valid menampilkan **Perkiraan panjang peta** dalam kilometer, dua angka desimal; panjang adalah jumlah semua segmen, bukan jarak awal–akhir. Point tidak mempunyai luas/panjang.
- Ukuran diperbarui saat input DD/DMS, klik peta, drag marker, tambah/hapus titik, ganti bentuk, dan bersihkan peta. Nilai stale tidak tampil ketika koordinat belum lengkap atau tidak valid. Polygon bersilangan/degenerat tidak diberi luas yang menyesatkan.
- Bandingkan dengan `water_area` pada `water_area_unit` yang sesuai: Hektar (Ha) untuk polygon, Kilometer (Km) untuk line. Dua nilai yang sama setelah pembulatan dua desimal tidak memicu warning; nilai berbeda memunculkan peringatan pada informasi peta, menyebut ukuran peta dan ukuran isian. Nilai 0 sah sebagai isian pembanding. Field kosong tidak dianggap 0. Satuan berbeda diberi petunjuk memilih satuan, bukan membandingkan besaran berbeda dimensi.
- Warning informatif: tidak memblokir save/export/submit, tidak mengganti isian pengguna atau membuat field wajib baru. Aturan kelengkapan, koordinat backend, ownership, approval, lock/revisi dan quota tetap berlaku.
- Perhitungan lokal dari koordinat WGS84 memakai model bumi bulat radius rata-rata 6.371.008,8 m: haversine untuk panjang, integral spherical ring untuk luas. Hektar = m²/10.000; kilometer = m/1.000. Ini perkiraan geometri, bukan luas legal/survei; dua desimal adalah format tampilan, bukan klaim akurasi survei. Antimeridian memakai selisih bujur terpendek. Tidak mendukung polygon berlubang/multipolygon; editor existing hanya satu ring.

## Desain dan batas akses

Modul JavaScript murni diekspor melalui entry Vite yang sudah ada; `coordinateManager` membaca points, shapeType serta state input dimensi Livewire secara reaktif. Tidak ada API AI/GMRT baru, dependency, layanan sistem, migration atau nilai computed yang ditulis ke DB/snapshot. Pengguna tetap mengisi dimensi yang menjadi isi dokumen. Hasil frontend tidak menjadi kontrol otorisasi atau bukti regulasi. Panel/route/service existing tidak berubah.

Verifikasi: Node built-in test runner untuk angka acuan, format, selisih/satuan, koordinat parsial, nol, polygon degenerat/bersilangan, ring terbalik/tertutup, antimeridian dan tidak ada mutasi; PHPUnit regresi Bab 1/akses; build; browser desktop dan mobile dengan fixture SQLite privat terisolasi. Seluruh perintah memakai `rtk`, tanpa Gemini berbayar atau database pengguna.

Referensi primer: [Turf area](https://turfjs.org/docs/api/area), [implementasi area](https://github.com/Turfjs/turf/blob/master/packages/turf-area/index.ts), [implementasi haversine](https://github.com/Turfjs/turf/blob/master/packages/turf-distance/index.ts). Rumus matematis diverifikasi terhadap sumber ini; tidak memasang package Turf atau menyalin library.

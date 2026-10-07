# Validasi batimetri GMRT — 7 Oktober 2026

> Regresi akses akun tanggal 2026-10-07: suite final **461 tests, 2429 assertions, lulus**, mencakup fitur pada laporan ini. Fixture kini memakai akun approved/owner; tiket/HP tidak lagi autentikasi. Hasil dan batas browser baru ada pada [validasi akun](kkprl-applicant-account-validation.md). Angka dan screenshot tahapan sebelumnya di bawah tetap bukti historis; batas ilmiah/validasi fitur dipertahankan.

Implementasi mengikuti [ADR-021](decisions/ADR-021-batimetri-gmrt-backend.md). Tidak memasang dependensi, mengubah .env pengguna, menjalankan migrasi data pengguna, atau memanggil Gemini berbayar.

## Pengujian otomatis

- RED awal: default sumber masih Garmin; tes pemilihan Batimetri gagal sebelum implementasi.
- 15 tes batimetri / 106 assertions: orientasi utara-barat, statistik fixture, NoData/daratan, grid datar, kontur bidang diketahui, posisi raster/penanda, koordinat tidak lengkap, area terlalu luas/antimeridian, respons salah/terlalu besar, timeout, akses/kunci/kuota/checkpoint/shape, AI gagal dan prompt tanpa arsip, penyimpanan manual, snapshot serta Word/PDF.
- Gabungan batimetri, profil dan gambar Bab 3: 44 tes / 284 assertions.
- Seluruh suite: **369 tes / 1.905 assertions**, lulus; 1 menit 39 detik, peak 350 MiB (suite termasuk pengujian XLS/XLSX pasut satu bulan). Semua pengujian memakai SQLite pengujian dan HTTP fake.
- Perubahan terakhir membatasi split baris/kolom/header parser sebelum alokasi; tes batimetri diulang dan tetap lulus. Pint 9 file PHP lulus setelah perapihan; build Vite lulus.

## Data sumber sebenarnya

Backend PHP melakukan satu GET publik pada titik sintetis (-0,48; 130,88) dengan tambahan 1 km, format esriascii, layer topo, mresolution 100. Respons 34 kolom × 33 baris, 1.122 sel valid, tidak ada NoData/daratan, elevasi -377,20 hingga -156,04 m, jarak keluaran sekitar 61,15 m; total fetch dan render 3,27 detik. Parser Python terpisah memeriksa jumlah nilai, min/max dan SHA-256, semuanya cocok. Ini data uji, bukan lokasi proposal pengguna. Header ASCII menjadi acuan koordinat raster; cakupan aktual dapat sedikit berbeda dari permintaan karena penyelarasan grid.

## Browser

Fixture sementara memakai SQLite, key sintetis, sesi/cache/storage terpisah. Respons GMRT berasal dari grid publik tersebut; panggilan AI selalu disimulasikan gagal tanpa biaya.

- Desktop: pemilihan Batimetri otomatis mengganti sumber ke GMRT, tombol membuat PNG privat 1100 × 900 dan narasi awal. Kredit, kontur, legenda, penanda, koordinat dan skala ditinjau secara visual.
- Koreksi manual dipertahankan saat AI gagal dan saat GMRT mengembalikan 503. Peta sebelumnya tetap dimuat dan tidak bertambah.
- Konfirmasi menyimpan draft; reload mempertahankan koreksi dan gambar privat.
- Ponsel 320 px: sumber default GMRT, kontrol tambahan 1 km dan tombol peta tampil; scrollWidth sama dengan viewport (320). Gambar privat termuat. Form alternatif terlihat setelah membuka **Unggah gambar batimetri dari sumber lain (opsional)**.
- Console tidak memuat error/warning. Screenshot dan bukti independen disimpan di `/tmp/kkprl-bathymetry-validation/`. Server dan seluruh fixture sementara dibersihkan setelah pengujian.

## Batas hasil

Rentang angka merujuk seluruh area peta, bukan titik usaha atau hanya polygon izin. Jarak keluaran bukan ketelitian/resolusi asli. Datum vertikal, periode survei dan ketelitian setempat tidak ditebak. Kontur menginterpolasi pusat sel valid; NoData tetap kosong. Data sekunder tetap perlu verifikasi setempat. Shared hosting menggunakan PHP GD yang sudah diperlukan penampang dan akses HTTPS ke GMRT, tanpa program GIS tambahan.

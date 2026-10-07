# ADR-020 — Penampang dasar laut langsung di Bab 3

Status: diterima untuk implementasi, 7 Oktober 2026.

Pelaku usaha membutuhkan grafik penampang tanpa mengunduh grid, menunggu email, atau memasang perangkat lunak. Pada pilihan Profil dasar laut, peta menampilkan koordinat Bab 1 sebagai referensi. Pengguna memilih A dan B atau mengisi koordinat desimal, kemudian meminta backend mengambil profil GMRT. Garis ini tidak mengubah koordinat atau batas lokasi Bab 1.

## Kontrak dan batas

- Satu lintasan great circle A–B sepanjang 10 m sampai 100 km; koordinat WGS84, lintang ±85° dan bujur ±180°. Batas aplikasi menjaga cakupan satu lintasan lokal, bukan batas layanan GMRT.
- Backend memanggil HTTPS `https://www.gmrt.org/services/ProfileServer` dengan `format=json`, timeout koneksi 5 detik dan total 20 detik, tanpa redirect, API key, retry otomatis, atau URL dari pengguna. Respons maksimal 256 KiB dan 1.000 sampel, divalidasi sebelum dipakai.
- Probe publik 7 Oktober 2026 menghasilkan 101 baris `[latitude, longitude, elevation_m, distance_m]`. Ini berlawanan dengan label kolom/urutan GeoJSON pada respons layanan. Gunakan JSON biasa dan verifikasi endpoint serta lintasan terhadap permintaan; jangan membaca kolom pertama sebagai bujur berdasarkan label yang keliru.
- Elevasi negatif dan positif tetap dipertahankan; nilai positif tidak diubah menjadi kedalaman laut. Titik kosong memutus garis, tidak diinterpolasi. Jarak sampel bukan resolusi grid, datum dan resolusi lokal tidak dinyatakan bila layanan tidak memberikannya. Statistik merupakan ekstrem sampel valid sepanjang lintasan, bukan ekstrem lokasi atau hasil survei.
- Backend menghasilkan grafik PNG dan narasi awal tanpa Gemini. Grafik, sampel, ringkasan, endpoint, waktu akses, checksum respons dan atribusi disimpan sebagai lampiran privat `ai_evidence` dengan anchor `seabed_profile`. Ini memakai kuota, akses, revision/snapshot, dan export gambar yang sudah ada. AI bersifat opsional dan mendapat ringkasan terhitung; ringkasan sumber tetap melekat pada narasi.
- Pratinjau tidak menjadi payload proposal sampai pengguna menekan Konfirmasi & Simpan Draft. Kegagalan sumber/AI mempertahankan grafik dan narasi sebelumnya. Akses, kunci proposal, koordinat Bab 1, dan checkpoint chat diperiksa kembali setelah permintaan eksternal.
- Map memakai Leaflet dan OpenStreetMap yang sudah digunakan proyek. Pemilihan A/B dan form desimal harus tetap tersedia bila tile atau script map gagal dimuat; tidak memasang dependensi baru di hosting.

## Keilmuan dan izin

GMRT adalah kompilasi data sekunder dengan cakupan dan resolusi bervariasi, termasuk grid global pengisi. Grafik tidak membuktikan jenis pasir, lumpur atau batuan, tidak menetapkan datum pasut, tidak menggantikan pengukuran di keramba, dan tidak digunakan untuk navigasi. Sumbu jarak dan elevasi memiliki skala berbeda. Cantumkan GMRT, link sumber, lisensi CC BY 4.0, tanggal akses dan bahwa grafik diolah aplikasi.

Sumber primer: [ProfileServer](https://www.gmrt.org/services/profileserverinfo.php), [overview](https://www.gmrt.org/about/index.php), [ketentuan CC BY 4.0](https://www.gmrt.org/about/terms_of_use.php), DOI data [10.1594/IEDA.100001](https://doi.org/10.1594/IEDA.100001).

## Verifikasi yang wajib

Respons publik nyata; tests validasi lokasi, angka, urutan/jarak, null, daratan, timeout, ukuran respons dan redirect; akses Bab 3, kuota, perubahan koordinat/checkpoint saat request; draft/manual review, kegagalan AI, provenance, snapshot dan export; browser desktop/mobile dengan fixture terisolasi, tombol reset A/B dan fallback form.

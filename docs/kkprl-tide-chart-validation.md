# Validasi grafik pasang surut backend

> Regresi akses akun tanggal 2026-10-07: suite final **461 tests, 2429 assertions, lulus**, mencakup fitur pada laporan ini. Fixture kini memakai akun approved/owner; tiket/HP tidak lagi autentikasi. Hasil dan batas browser baru ada pada [validasi akun](kkprl-applicant-account-validation.md). Angka dan screenshot tahapan sebelumnya di bawah tetap bukti historis; batas ilmiah/validasi fitur dipertahankan.

Tanggal: 2026-10-07. Implementasi: [ADR-023](decisions/ADR-023-grafik-pasut-backend.md).

## Pengujian ilmiah dan integrasi

- Normalisasi seri memakai parser yang sama dengan statistik/harmonik: urut UTC, tinggi meter, mapping/format/tanggal/jam/datum yang dipilih. Kolom UTC dan WIT pada BIG menghasilkan instan serta tinggi fisik identik.
- Semua 44.641 sampel fixture 31 hari dipertahankan, termasuk ekstrem tunggal di tengah seri. Nilai invalid/duplikat tetap ditolak. Data negatif dan konstan menghasilkan sumbu hingga dengan rentang tidak nol.
- Celah sepuluh jam pada fixture terputus di lokasi waktu yang benar; pemeriksaan piksel memastikan tidak ada kurva biru di tengah celah. Ambang 1,5 kali median interval adalah kebijakan visual aplikasi, bukan standar otoritas.
- PNG disimpan privat, diunduh hanya dengan akses yang sah, dan mengikuti snapshot. Pengujian Word membandingkan SHA-256 gambar yang benar-benar tertanam dengan PNG asli; PDF memuat objek gambar 1600 × 1080 beserta caption/sumber.
- Pembuatan grafik tidak mengganti narasi manual atau menyimpan payload otomatis. Klik berulang tidak menambah lampiran. Tabel terbaru diprioritaskan. File sumber berubah, PNG lama dirusak, kuota penuh, akses proposal lain dan draft terkunci ditangani tanpa menghapus sumber/narasi/gambar lama.

## Pemeriksaan independen CSV BIG

CSV asli `docs/pasut/data_srgi.csv` tidak diubah. Parser Python standar (`csv`, `datetime`, `Decimal`, `hashlib`) digunakan hanya untuk memverifikasi hasil, tanpa menjadi dependency aplikasi:

- **41.760 sampel**, 2026-09-01 00:00:00 sampai **2026-09-29 23:59:00 UTC**, interval 60 detik. File contoh berisi 29 hari; aplikasi tidak menambah atau memotongnya menjadi bulan penuh.
- Minimum **−0,900 m** pada 29 September 17:26 UTC; maksimum **0,845 m** pada 29 September 10:59 UTC; rata-rata aritmetika **−0,000241762452107279693… m**. Hasil backend cocok dalam presisi floating point; rata-rata ini tidak digunakan untuk menetapkan MSL.
- Ambang garis 90 detik, nol jeda yang melampaui ambang. Source SHA-256 `42ad3e086a9dc84e3cfcb6b90be5f92e84bcae994e8a1f461c683cb77b46e6d1` cocok antara file asli, salinan privat dan metadata grafik.
- Gambar diperiksa secara visual: kurva dua arah, sumbu tanggal/jam, datum dari sumber, label prediksi, tanda ekstrem, sumber BIG/kredit, stasiun PAAM dan koordinat sumber. Tidak ada lisensi GMRT yang dilekatkan pada grafik BIG.

## Browser dan lingkungan

Browser memakai SQLite, sesi/cache/storage/APP_KEY sintetis terisolasi. Seluruh HTTP eksternal aplikasi diblokir; tidak ada request Gemini berbayar. CSV fixture adalah salinan file asli.

Desktop: gambar 1600 × 1080 termuat, tombol buat dan lihat/unduh tersedia. Koreksi manual bertahan setelah membuat grafik dan setelah konfirmasi/reload. Klik berulang tetap menghasilkan satu CSV dan satu PNG. Menghapus grafik lalu membuatnya kembali juga tidak mengganti narasi.

Ponsel 320 px: `scrollWidth=320`, gambar termuat, tautan gambar resolusi penuh tersedia, tidak ada alert atau error/warning console setelah reload.

Bukti disimpan di `/tmp/kkprl-tide-chart-validation/`, termasuk PNG asli/backend/browser, screenshot desktop/ponsel, verifikasi DOM, perhitungan Decimal independen dan laporan JUnit. Server serta direktori fixture dibersihkan setelah validasi. Tidak ada migrasi/dependency baru, instalasi sistem, atau perubahan .env/database/file CSV pengguna.

## Hasil akhir

- Suite lengkap: **394 tes, 2.052 assertion**, tanpa failure/error/skipped; sekitar 3 menit 20 detik, memori puncak 370 MiB pada mesin pengembangan.
- Pint: keenam file PHP yang disentuh lulus. Build Vite dan pemeriksaan whitespace perubahan lulus.
- Fokus grafik: 6 tes numerik/PNG serta 7 tes alur/integrasi; fungsi parser, perhitungan harmonik dan semua fitur proposal lainnya ikut diuji pada suite lengkap.

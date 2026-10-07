# Validasi kelengkapan Bab 5 — 7 Oktober 2026

> Regresi akses akun tanggal 2026-10-07: suite final **461 tests, 2429 assertions, lulus**, mencakup fitur pada laporan ini. Fixture kini memakai akun approved/owner; tiket/HP tidak lagi autentikasi. Hasil dan batas browser baru ada pada [validasi akun](kkprl-applicant-account-validation.md). Angka dan screenshot tahapan sebelumnya di bawah tetap bukti historis; batas ilmiah/validasi fitur dipertahankan.

Perubahan mengikuti [ADR-022](decisions/ADR-022-kelengkapan-bab-lima-kondisional.md). Pengguna mengonfirmasi dua kondisi: belum mempunyai izin pendukung dan sudah mempunyai izin tetapi dokumennya belum tersedia.

## Akar masalah dan kontrak

Progress sebelumnya menyamakan daftar semua field template dengan field wajib. Akibatnya nomor izin, penerbit, masa berlaku, dokumen dan catatan kosong menghalangi Bab 5 meskipun rincian pokok telah diberikan.

Katalog kini memisahkan field wajib dari seluruh field: izin yang ada memerlukan jenis, tahun perolehan dan status; lahan yang berhimpitan memerlukan status, jenis bukti dan tahun perolehan. Detail lain opsional, tetap ditampilkan, disimpan dan diekspor jika diisi. Pengguna yang belum memiliki izin memilih kondisi tersebut pada Bab 5; pilihannya masuk checkpoint dahulu, lalu memperbarui kondisi Bab 1 saat konfirmasi. Bagian izin nonaktif tidak dihitung wajib. Jika bukti lahan juga tidak relevan, Bab 5 dilewati. Data lama dipertahankan untuk koreksi.

## Pengujian

- RED kelengkapan: jenis/tahun/status izin terisi, rincian opsional kosong, tetapi status incomplete sebelum perbaikan.
- RED lampiran: menonaktifkan izin dengan lampiran inline izin lama menghalangi ekspor lahan karena anchor tidak aktif. Lampiran kini tetap privat tetapi dikeluarkan dari snapshot jika field/anchor milik bagian nonaktif. Anchor yang benar-benar tidak dikenali tetap ditolak.
- 10 tes fitur baru serta 2 tes progress: pilihan sementara, konfirmasi, resume, restorasi koreksi, kedua kondisi pengguna, detail opsional, ekspor Word/PDF, akses/kunci/nilai/scope, prompt AI, penolakan patch bagian nonaktif, kondisi Bab 1 berubah pada tab lain, snapshot legacy/nested, dan preservasi lampiran.
- Regresi chat/wizard/generator dokumen: 65 tes / 481 assertions, lulus sebelum penambahan tes lampiran terakhir.
- Seluruh suite **381 tes / 1.968 assertions**, lulus; 3 menit 9 detik, peak 346 MiB. Setelah memprioritaskan anchor aktif yang sudah valid, tes fitur Bab 5 dan snapshot diulang: 14 tes / 68 assertions, lulus.
- Pint 7 file PHP dan build Vite lulus.

## Browser wizard sebenarnya

Fixture SQLite, sesi/cache/storage dan APP_KEY sintetis terpisah dari data pengguna. Semua HTTP eksternal disimulasikan; tidak ada panggilan Gemini berbayar.

1. Lokasi berhimpitan, informasi lahan pokok terisi, izin belum dilengkapi: progress 94%. Pilih **Belum memiliki izin pendukung**, konfirmasi: progress 100%, kolom izin hilang, bukti lahan tetap tampil, Bab 5 berstatus Data Lengkap.
2. Ganti kembali ke **Sudah memiliki izin pendukung**: koreksi nomor izin lama muncul kembali. Isi jenis Izin usaha, tahun 2025, status Berlaku. Semua lima rincian opsional dikosongkan. Konfirmasi: progress 100%, tanpa validation error; reload mempertahankan tiga informasi pokok dan pilihan.
3. Lokasi tidak berhimpitan, kemudian pilih belum memiliki izin: konfirmasi menghilangkan Bab 5 dari daftar bab dan mengembalikan wizard ke Bab 3. Progress tetap 100%, tanpa validation error.
4. Ponsel 320 px: scrollWidth=320, dropdown dan label opsional dapat dibaca. Bukti browser di `/tmp/kkprl-chapter-five-validation/`. Final console setelah reload bersih; emulasi sempat menghasilkan error jaringan sementara ERR_NETWORK_CHANGED.

Server lokal dan seluruh fixture dibersihkan setelah pengujian. Tidak ada migrasi, perubahan .env pengguna, atau perubahan massal draft lama.

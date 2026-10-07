# Validasi ukuran peta Bab 1 — 8 Oktober 2026

Lingkup: perkiraan luas polygon dalam ha, panjang line dalam km, dua desimal, peringatan selisih dengan isian dimensi. [Spesifikasi](kkprl-map-measurement-spec.md), [ADR-028](decisions/ADR-028-informational-map-dimensions.md).

## Implementasi

Modul murni `resources/js/kkprl-coordinate-measurement.js` diekspor melalui entry Vite existing. Getter pada coordinateManager membaca daftar DD hasil input DD/DMS, klik/drag peta dan nilai dimensi Livewire lokal; warning tidak menunggu roundtrip autosave. Tidak ada penulisan nilai computed, perubahan migration/route/service, dependency baru atau panggilan AI/GMRT.

Radius spherical rata-rata 6.371.008,8 m; area dibagi 10.000 untuk ha, panjang seluruh segmen dibagi 1.000 untuk km. Dua nilai dibandingkan pada pembulatan dua desimal. Isian tidak diubah otomatis, termasuk saat berbeda. Satuan berbeda dan isian angka tidak valid mendapat petunjuk. Geometri kosong/parsial/out-of-range tidak menghasilkan angka stale; polygon bersilangan/degenerat ditandai tidak dapat dihitung.

## Pengujian aktual

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| RED modul belum ada | `rtk proxy node --test tests/js/kkprl-coordinate-measurement.test.mjs` | Exit 1, ERR_MODULE_NOT_FOUND pada modul yang belum dibuat |
| Geometri dan warning | `rtk proxy node tests/js/kkprl-coordinate-measurement.test.mjs` | 10 tests PASS, exit 0; Node 24.19.0 built-in node:test |
| Syntax JavaScript | `rtk proxy node --check resources/js/kkprl-coordinate-measurement.js` dan `rtk proxy node --check resources/js/app.js` | Exit 0 |
| Regresi wizard/progress/input | `rtk proxy php vendor/bin/phpunit --filter='KkprlChapterOneInputTest\|KkprlProposalWizardTest\|ProposalProgressTest\|KkprlApplicantAccountTest'` | 50 tests/140 assertions PASS; nama ApplicantAccountTest tidak cocok kelas tersendiri, otorisasi akun diuji oleh suite penuh |
| Suite aplikasi | `rtk proxy php vendor/bin/phpunit` | 469 tests/2462 assertions PASS; 5 menit 37,095 detik; exit 0 |
| Build UI | `rtk proxy npm run build` | Vite 7.3.6, 59 modules, 3,60 detik; exit 0 |

Pada host ini `node --test` menampilkan satu file sebagai hasil agregat. Perintah langsung pada file node:test menampilkan dan menjalankan kesepuluh kasus secara eksplisit; laporan tidak mengklaim 10 kasus dari output agregat satu file. Tes mencakup angka acuan ekuator, jumlah panjang segmen, perbandingan dua desimal, satuan berbeda, nol/kosong/malformed, batas koordinat, point, line nol, polygon ring tertutup/terbalik, tanpa mutasi input, polygon bersilangan/degenerat dan antimeridian. Square 0,001° di ekuator menghasilkan 1,2364345868 ha (tampil 1,24); lintasan dua segmen 0,01° menghasilkan 2,22390160467 km (tampil 2,22).

PHPUnit memakai SQLite :memory:, session/cache array, fake/mock layanan eksternal. Suite penuh mencakup pending/approved, ownership silang, lock/revisi, private storage/integritas, workflow petugas, Bab 1–5, pasut/grafik, GMRT dan export Word/PDF. API Gemini berbayar tidak dipakai. Database pengguna dan .env tidak diubah. Log tes lokal: storage/logs/kkprl-map-measurement-targeted.txt dan kkprl-map-measurement-full-suite.txt.

## Browser aktual

Chrome DevTools context baru `kkprl-map-measurement-20261008`, localhost:8767, database SQLite/file session/cache/storage/log/key fixture baru di storage/framework/testing/kkprl-map-measurement-20261008. Http::preventStrayRequests aktif. Input memakai data sintetis dan tidak membuka sesi/data pengguna.

- Line dua titik ekuator tampil **1,11 km**. Isian **2,00 km** memunculkan warning dengan kedua angka; mengubah isian ke **1,11** menghilangkannya segera saat field masih fokus.
- Polygon tiga titik dari DD dan DMS tampil **0,62 ha**. Isian **0,62** tanpa warning; **1,00 ha** menampilkan warning. Data isian tetap **1** pada state Livewire.
- Bersihkan peta menghilangkan hasil sebelumnya dan kembali ke petunjuk minimal titik. Tiga klik peta setelah pan keyboard membentuk area baru; angka luas dan warning diperbarui dari titik gambar, bukan input manual saja.
- Drag marker sehingga tiga titik menjadi segaris menghasilkan pesan luas tidak dapat dihitung; hasil luas sebelumnya tidak tersisa. Ganti Point menyembunyikan panel ukuran. Latitude **999** pada Line mengganti angka dengan petunjuk koordinat lengkap/valid.
- Desktop dan emulasi mobile **320 × 740** diperiksa; innerWidth dan scrollWidth sama-sama 320. Peringatan dan angka dua desimal terbaca/wrap tanpa overflow horizontal. Console bersih dari error/warning.

Screenshot lokal fixture: /tmp/kkprl-map-measurement-desktop.png dan /tmp/kkprl-map-measurement-mobile.png. Ini bukan bukti survei/pemohon. Koneksi layanan nyata dan jaringan shared hosting tidak diuji; CDN editor/peta existing tetap digunakan. Warning adalah informasi geometri model spherical; tidak mengubah aturan submit/export atau menjadi bukti ilmiah/legal.

## Review dan Graphify

Review mencakup unit dan konversi, pembulatan/format Indonesia, strict parsing tanpa menganggap kosong sebagai nol, geometri invalid, perubahan reactive dari field fokus, tidak adanya mutasi/persist ukuran, serta tidak bertambahnya layanan/dependency. Asset generated sebelum build disalin kembali jika hilang agar pekerjaan terdahulu tetap tersedia. Tidak dilakukan reset/clean, commit/push/deploy.

Graphify utama diperbarui dari sumber JavaScript, Blade, tes dan dokumentasi final. Baseline memakai indeks utama sesudah ADR-027, bukan subgraf atau baseline sebelum perbaikan input. [GRAPH_REPORT](../graphify-out/GRAPH_REPORT.md) dan .graphify_update.json mencatat hash sumber, manifest, cakupan, arah relasi, endpoint dan relasi ambigu. Cakupan tetap indeks utama proyek/KKPRL; relasi parser ambigu bukan bukti runtime.

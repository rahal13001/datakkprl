# ADR-010 — Format dan validasi koordinat lokasi

## Status

Accepted. Diperbarui 2026-10-04 untuk menyesuaikan editor peta yang sudah tersedia.

## Decision

Bab 1 memakai `shape_type` (`point`, `line`, `polygon`), `coordinates` sebagai teks dokumen, dan `coordinates_raw` sebagai JSON daftar titik untuk editor peta. Nilai `lat_dd`/`lng_dd` pada setiap titik memakai angka desimal WGS84. Input DD/DMS dan klik peta dikonversi oleh editor ke format tersebut.

Latitude harus -90 sampai 90 dan longitude -180 sampai 180, termasuk batasnya. Titik membutuhkan minimal satu pasangan koordinat, garis dua titik, dan polygon tiga titik. JSON rusak, daftar kosong, titik parsial, nilai nonnumerik, dan bentuk tidak dikenal menghasilkan error bab sehingga export dan submit ditolak. Draft parsial tetap dapat disimpan untuk dikoreksi.

`coordinates_raw` dipertahankan pada payload nested maupun flat, termasuk saat konfirmasi pratinjau AI mengubah draft flat menjadi nested. Raw tidak menjadi field dokumen. Jika raw belum tersedia, validator membaca pasangan desimal dalam format teks editor (`Titik ...: Latitude ... (...), Longitude ... (...)`). Nilai eksplisit `Tidak berlaku` tetap diterima untuk teks tanpa raw sesuai keputusan MVP sebelumnya.

Perubahan peta disimpan otomatis setelah pemeriksaan session dan penguncian proposal. Data terbaru ikut dalam request navigasi meskipun debounce belum berjalan. Widget peta memakai `wire:ignore` dan membersihkan timer/layer saat meninggalkan bab. Konversi DD/DMS membulatkan satuan detik terlebih dahulu agar detik tidak tampil sebagai `60.0000`.

## Rationale

Katalog dan UI telah berpindah dari pasangan field `latitude`/`longitude` ke daftar titik, tetapi validator dan beberapa fixture masih memakai format lama. Validasi perlu memakai data yang benar-benar tersimpan agar input di luar rentang tidak lolos submit.

## Consequence

Dokumen menampilkan bentuk dan daftar koordinat di section PETA LOKASI. Test memakai fixture format katalog saat ini. Validasi ini tidak menerbitkan atau memvalidasi izin resmi.

# ADR-029 — Perbaikan aditif skema institusi proposal

- Status: Accepted
- Tanggal: 2026-10-09
- Melengkapi ADR-006, ADR-014, ADR-016 dan ADR-027. Tidak mengganti format payload, aturan akses atau integritas dokumen.

## Konteks

Hosting melaporkan kegagalan autosave karena kolom `institution_name` tidak ada. Hasil pemeriksaan pengguna menunjukkan migration institusi lama tercatat selesai pada batch 23, sementara kedua kolom institusi tidak ada pada skema aktual. Penyebab historis belum terverifikasi. Laravel menjadwalkan migration berdasarkan file dan riwayat eksekusi, bukan rekonsiliasi semua kolom tabel.

## Keputusan

Gunakan migration baru dengan pemeriksaan `Schema::hasColumn` terpisah untuk `institution_name` dan `institution_name_encrypted`. Tambahkan kolom yang hilang sebagai TEXT nullable, tanpa `after`, backfill, UPDATE atau perubahan migration lama. Jangan melewati kegagalan jika tabel proposal tidak ada. Skema utuh tetap diterima, dan eksekusi setelah kegagalan DDL parsial dapat melanjutkan kolom yang tersisa.

`down()` tidak menghapus kolom. Pada skema parsial, kolom yang sudah ada bukan milik migration ini; sesudah perbaikan, keduanya mungkin telah menyimpan data. Tanpa provenance per kolom, penghapusan saat rollback berisiko merusak data. Rollback kode mempertahankan ekspansi skema; pemulihan database menggunakan backup/prosedur operator terpisah. Jalur down diuji untuk memastikan data tetap utuh.

Tidak ada endpoint perbaikan, permission baru, bypass setter encryption, atau fallback yang mengabaikan kesalahan autosave. Ownership, approval, submitted/locked, revisi, quota, private storage, snapshot dan hash existing tetap berlaku. Migration sendiri tidak memperbarui payload atau mengubah validitas dokumen lama.

## Alternatif

- Mengedit atau memaksa ulang migration lama: riwayat sudah menandainya selesai; skema parsial juga dapat memunculkan duplicate column. Tidak menyelesaikan transisi deployment dengan jelas.
- Menghapus baris pada tabel migration atau menjalankan fresh/rollback: dapat mengubah urutan/riwayat dan menghapus data. Tidak dipakai.
- Menghapus penulisan `institution_name` pada model: mengubah kontrak encryption existing dan tidak mengatasi kolom encrypted yang juga hilang.
- ALTER TABLE manual tanpa migration: dapat memperbaiki satu server, tetapi tidak menyediakan artefak deploy/test yang tercatat untuk lingkungan lain.

## Batas dan konsekuensi

Backup database dilakukan operator sebelum DDL pada hosting. Jalankan satu proses migration dari release dan koneksi aplikasi yang benar. Pemeriksaan kolom bukan perlindungan terhadap DDL konkuren. Tidak ada transaksi DDL MySQL yang diasumsikan. Migration mempertahankan row existing, tetapi tidak dapat mengembalikan input yang gagal tersimpan atau data kolom yang sebelumnya hilang.

Tes SQLite terisolasi membuktikan empat keadaan skema, penjadwalan Artisan, autosave terenkripsi, row revisi/artifact dan hash tetap utuh. Verifikasi database MySQL hosting dilakukan setelah operator menerapkan perbaikan; tidak diklaim dari hasil SQLite. Tidak ada instalasi sistem atau layanan baru.

Lihat [spesifikasi](../kkprl-institution-schema-repair-spec.md) dan [validasi/runbook](../kkprl-institution-schema-repair-validation.md). API migration mengikuti [dokumentasi Laravel 13](https://laravel.com/docs/13.x/migrations).

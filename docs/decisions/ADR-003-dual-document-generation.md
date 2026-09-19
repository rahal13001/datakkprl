# ADR-003: Generate Word dan PDF dari Snapshot yang Sama

## Status

Accepted and implemented for MVP

## Date

2026-08-24

## Context

Pemohon membutuhkan dokumen yang dapat langsung diunduh dalam format Word atau PDF. Petugas juga perlu meninjau hasil yang sama dari Filament. Proposal memiliki banyak bab dan lampiran, sehingga satu file gabungan berpotensi besar dan mahal diproses.

Jika Word dan PDF dibuat dari proses data berbeda, isi keduanya dapat tidak konsisten. Dependensi generator DOCX belum ada di aplikasi saat keputusan ini dibuat.

## Decision

Gunakan export per bab sebagai scope utama MVP. Saat pemohon meminta generate:

1. Sistem memvalidasi bab yang diminta.
2. Sistem membuat snapshot data bab.
3. Sistem menghasilkan Word dan PDF dari snapshot bab yang sama.
4. Sistem menyimpan chapter, versi template, hash snapshot, manifest lampiran, status generation, dan lokasi file privat.
5. Sistem memperlakukan kegagalan salah satu format sebagai kegagalan parsial yang dapat diulang.
6. Sistem tidak wajib membuat file gabungan seluruh bab pada MVP.

Saat proposal dikirim sebagai submitted, service submit menjalankan generate DOCX dan PDF untuk setiap bab aktif dari snapshot yang sama sebelum status dikunci. Jika salah satu format gagal, transaksi dibatalkan dan proposal tetap draft; pemohon tidak menerima status submitted yang belum memiliki artefak lengkap.

Lampiran yang dipetakan ke bab ikut digabung ke Word dan PDF bab tersebut. Format lampiran MVP dibatasi pada PDF, JPG/JPEG, PNG, dan WebP. Setiap bab membatasi maksimal 10 lampiran dengan total ukuran gabungan maksimal 15 MB. Gambar dapat ditempatkan inline tepat setelah teks acuannya atau pada appendix bab. Gambar/tabel harus dilayout secara profesional: rasio gambar dipertahankan, caption dan nomor lampiran konsisten, tabel dapat mengulang header, dan halaman landscape dipakai bila diperlukan. PDF harus dirender atau dikonversi secara terkontrol agar terbaca di kedua format. WebP boleh dinormalisasi hanya pada salinan render menjadi PNG/JPEG bila engine output memerlukannya; file asli tetap dipertahankan.

Generator memakai katalog heading section resmi per bab yang dipetakan ke field payload serta katalog label field Indonesia yang dipakai bersama oleh DOCX dan PDF. Heading resmi hasil ekstraksi XML template disimpan sebagai fixture golden `tests/Fixtures/kkprl/official-sections.golden.json` dan diuji pada kedua format. Numbering level-0 template resmi (`upperRoman`) untuk Bab 1, 3, 4, dan 5 disimpan di `tests/Fixtures/kkprl/official-section-numbering.golden.json` serta direpresentasikan sebagai prefix Roman yang sama pada DOCX dan PDF; Bab 2 dipertahankan tanpa prefix karena XML resminya tidak memakai `numPr`. Ini membuat hasil tidak bergantung pada engine viewer. Setiap output memiliki cover page terpisah sebelum isi bab agar struktur dasar template resmi tetap dipertahankan. Katalog ini hanya memengaruhi susunan dokumen, sehingga urutan field pada wizard tetap dapat dioptimalkan untuk input mobile tanpa mengubah kontrak data. Cover, heading, label, dan nilai menggunakan snapshot yang sama untuk DOCX dan PDF.

Strategi teknis generator Word dan PDF dipilih setelah spike kecil yang menguji template nyata.

## Alternatives Considered

### Menghasilkan PDF saja

- Pro: sudah ada `barryvdh/laravel-dompdf`.
- Kontra: tidak memenuhi kebutuhan Word.
- Ditolak: Word adalah output wajib.

### Menghasilkan satu file gabungan seluruh proposal

- Pro: satu berkas mudah dibagikan.
- Kontra: ukuran file lebih besar, proses lebih berat, dan satu kegagalan dapat menggagalkan seluruh output.
- Ditunda: export per bab lebih sesuai dengan kebutuhan parsial MVP.

### Menghasilkan Word dan PDF dari dua payload terpisah

- Pro: tiap format dapat dioptimalkan sendiri.
- Kontra: risiko perbedaan isi dan aturan kondisional.
- Ditolak: snapshot tunggal lebih dapat diaudit.

### Menyimpan lampiran sebagai file terpisah

- Pro: proses lebih ringan dan storage hasil lebih kecil.
- Kontra: pengguna harus mengunduh dan mengatur banyak file; format dokumen menjadi kurang praktis.
- Ditolak: lampiran harus ikut berada di dalam Word dan PDF per bab.

## Consequences

Positif:

- Word dan PDF dapat dibandingkan berdasarkan snapshot hash.
- Petugas meninjau output yang sama dengan pengguna.
- Generate ulang dapat dilacak berdasarkan versi template.

Negatif dan mitigasi:

- Membutuhkan library/strategi DOCX baru; lakukan spike sebelum implementasi penuh.
- File hasil generate harus memiliki retensi dan private download policy.
- Sistem membutuhkan aturan kelengkapan per bab dan daftar dokumen per bab di Filament.
- Ukuran dan waktu generate dapat meningkat karena lampiran digabung; mitigasinya adalah export per bab, cache snapshot, batas upload, dan fallback ke proses asynchronous bila diperlukan.
- Kualitas hasil sangat bergantung pada strategi render PDF dan penempatan gambar inline; ini wajib menjadi bagian spike generator dan golden-file test.

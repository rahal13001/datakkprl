# ADR-008: Export Proposal dengan Scope Per Bab

## Status

Accepted for MVP

## Date

2026-08-24

## Context

Proposal KKPRL terdiri dari beberapa bab dan lampiran. Jika seluruh bab digabung, ukuran file dan waktu proses dapat meningkat. Pengguna juga dapat membutuhkan satu bab tertentu lebih dahulu.

## Decision

Export MVP menggunakan scope per bab:

- Bab 1, Bab 2, dan Bab 3 dapat diekspor masing-masing setelah lengkap.
- Bab 4 hanya dapat diekspor jika reklamasi aktif dan seluruh field Bag 4 lengkap.
- Bab 5 hanya dapat diekspor untuk subbagian aktif dan lengkap.
- Setiap bab memiliki Word dan PDF.
- Lampiran yang dipetakan ke bab ikut digabung ke Word dan PDF bab tersebut.
- Format lampiran MVP dibatasi pada PDF, JPG/JPEG, PNG, dan WebP.
- Setiap bab memiliki batas maksimal 10 lampiran aktif dan total ukuran gabungan maksimal 15 MB.
- Gambar dapat ditempatkan inline setelah teks acuannya atau pada appendix bab.
- Download hanya membaca payload/lampiran bab yang relevan; lampiran dari bab lain tidak diproses.
- Gambar dan tabel harus diposisikan dengan rasio, caption, page break, dan orientasi halaman yang sesuai.
- File gabungan seluruh bab ditunda dan bukan acceptance criterion MVP.
- Status `submitted` tetap mengunci seluruh proposal revision; export per bab tidak mengubah status.

## Alternatives Considered

### Satu file gabungan sejak awal

- Pro: satu berkas mudah dibagikan.
- Kontra: lebih besar, lebih lambat, dan seluruh proses gagal jika satu bab bermasalah.
- Ditolak untuk MVP.

### Export tanpa validasi bab

- Pro: pengguna dapat mengunduh kapan saja.
- Kontra: menghasilkan dokumen parsial yang tidak jelas kelengkapannya.
- Ditolak: bab harus lengkap sebelum diekspor.

## Consequences

Positif:

- Beban render dan storage lebih kecil per permintaan.
- Pengguna dapat bekerja dan mengunduh secara parsial.
- Kegagalan satu bab tidak menghambat bab lain.
- Pengguna menerima dokumen bab yang lebih mandiri dan lebih dekat dengan format pengajuan OSS.

Negatif:

- Pengguna menerima beberapa file.
- Filament perlu menampilkan status dan dokumen per bab.
- Setiap dokumen bab dapat menjadi lebih besar karena lampiran ikut digabung; batas ukuran dan strategi proses harus diuji.
- Strategi penggabungan seluruh bab perlu keputusan baru jika dibutuhkan kemudian.

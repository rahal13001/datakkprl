# ADR-013: Generasi Dokumen Sinkron dengan Batas MVP

## Status

Accepted for MVP; operational threshold review remains required before production scale-up.

## Context

Export KKPRL berjalan per bab, bukan satu proposal gabungan. Setiap bab membatasi 10 lampiran aktif dan total 15 MiB. Generator menyimpan status `generating`, `generated`, atau `failed`, menggunakan snapshot dan manifest deterministik, serta memiliki retry idempotent.

Proses eksternal yang merender PDF lampiran memiliki timeout. Namun, pemindahan seluruh proses ke queue akan mengubah kontrak submit dan membutuhkan worker, monitoring, serta UI polling yang belum menjadi acceptance criterion MVP.

## Decision

1. MVP memakai generasi sinkron per bab.
2. Batas 10 file dan 15 MiB per bab menjadi guardrail request langsung.
3. UI menampilkan state loading; hasil hanya dapat diunduh ketika status `generated`.
4. Kegagalan menyimpan `generation_status=failed` dan kode error non-sensitif; retry memakai record snapshot yang sama.
5. Deploy production wajib memantau durasi request, memory, timeout renderer, dan jumlah kegagalan. Jika threshold operasional terlampaui, generasi dipindahkan ke queue sebagai perubahan terencana.
6. Queue worker bukan prasyarat acceptance MVP, tetapi keputusan ini tidak mengizinkan penghapusan status generation atau retry yang aman.

## Consequences

Positif:

- Kontrak export dan submit tetap sederhana serta dapat diverifikasi dalam satu request.
- Tidak ada artefak setengah jadi yang terlihat sebagai dokumen selesai.
- Batas lampiran membatasi ukuran kerja per request.

Negatif:

- Bab dengan lampiran visual mendekati kuota dapat menggunakan CPU/memory tinggi.
- Production harus memiliki timeout web yang kompatibel dan monitoring sebelum membuka trafik besar.
- Queue perlu diimplementasikan sebelum skala atau durasi aktual melampaui threshold yang disetujui.

## Verification

- Full PHPUnit mencakup snapshot bersama, retry idempotent, PDF renderer timeout/failure, PDF lampiran image-only, dan status generation.
- Strict preflight memeriksa renderer PDF dan capability image normalization.
- UAT manual tetap wajib memvalidasi ukuran file nyata, viewer Word/PDF, dan durasi pada environment target.

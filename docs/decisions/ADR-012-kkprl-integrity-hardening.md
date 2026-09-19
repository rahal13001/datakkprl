# ADR-012 — Integrity dan Enumeration Hardening KKPRL

## Status

Accepted for MVP.

## Date

2026-08-24

## Context

Proposal utama dan revisi wajib memakai nomor tiket utama yang sama. Pada saat yang sama, kuota lampiran harus konsisten terhadap upload paralel, dan endpoint download tidak boleh mengungkap apakah ID artefak milik proposal tertentu ada.

## Decision

- Database memakai index `ticket_number` dan unique composite `(ticket_number, revision_number)`. Nomor tiket dapat dipakai bersama oleh revisi, sementara dua proposal utama tidak dapat berbagi tiket pada revision 0.
- Operasi store, replace, dan delete lampiran mengunci row proposal parent selama transaksi sebelum menghitung ulang kuota per bab.
- Download dokumen/lampiran mengembalikan respons 404 generik untuk akses yang tidak terotorisasi, sehingga perbedaan antara artefak yang ada dan tidak ada tidak menjadi oracle enumeration.
- Download dokumen juga mencocokkan ulang snapshot payload dan manifest lampiran terhadap proposal saat ini. Dokumen draft yang stale, atau dokumen dari bab kondisional yang sudah tidak aktif, dikembalikan 404 dan tidak ditampilkan kembali oleh wizard.
- Resume memakai dua lapis rate limit: per pasangan tiket/HP/IP dan per IP, sehingga attacker tidak dapat melewati batas hanya dengan mengganti pasangan credential.
- Model proposal terkunci tidak dapat dibuka kembali menjadi draft, dihapus, atau dimutasi selain transisi terkontrol `submitted` ke `needs_revision`.
- Pilihan reklamasi pada form memakai nilai eksplisit `Pilih`, `Ya`, atau `Tidak`; Bab 4 hanya relevan untuk nilai truthy.

## Consequences

Positif:

- Integritas nomor tiket tetap kompatibel dengan revision family.
- Batas 10 file/15 MiB terlindungi pada transaksi yang bersaing.
- Akses publik tidak membocorkan keberadaan proposal atau artefak melalui status HTTP.
- Proposal submitted tidak dapat dibuka kembali lewat update model langsung.

Negatif:

- Perubahan status yang sah harus melalui service domain.
- Respons 404 untuk staff tanpa permission sedikit mengurangi detail diagnosis, tetapi lebih aman untuk boundary download publik.

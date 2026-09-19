# ADR-009: Renderer Snapshot dan Artefak Privat KKPRL

## Status

Accepted for MVP implementation

## Date

2026-08-24

## Context

Proposal dapat berubah selama draft. Word/PDF per bab harus merepresentasikan data dan manifest lampiran yang sama, sementara file pemohon tidak boleh menjadi file publik. Runtime tidak memiliki dependensi converter DOCX eksternal yang sudah disetujui.

## Decision

- Submit final meng-generate kedua format untuk semua bab aktif di dalam transaksi sebelum status proposal menjadi submitted; kegagalan render mempertahankan status draft.

- Export selalu dimulai dari `KkprlProposalSnapshotService` setelah bab relevan lengkap.
- Snapshot payload dan manifest lampiran disimpan terenkripsi pada record dokumen; hash snapshot dan hash manifest menjadi identitas artefak.
- Word dan PDF dalam satu panggilan generator memakai objek snapshot yang sama.
- DOCX dibuat sebagai package OOXML minimal dengan gambar inline/appendix sebagai media internal.
- PDF dibuat melalui DomPDF dari view yang sama secara semantik.
- PDF bab memakai A4 portrait secara default dan beralih ke A4 landscape bila bab memiliki tabel lebih dari empat kolom; Word memakai section break landscape untuk tabel lebar.
- Renderer mendukung mode `png` untuk binary `pdftoppm` modern dan mode `bmp` untuk binary Poppler lama seperti `pdftobmp`; hasil BMP dinormalisasi menjadi PNG salinan sebelum dimasukkan ke DOCX/PDF. Mode dipilih melalui `KKPRL_PDF_RENDERER_FORMAT`.
- PDF tanpa teks yang membutuhkan render visual wajib memiliki renderer yang berhasil; jika tidak, generation gagal dengan status `failed` dan tidak memakai placeholder sebagai hasil sukses.
- WebP asli tidak diubah. Normalisasi ke PNG hanya terjadi pada salinan render.
- Semua artefak dan lampiran baru disimpan pada disk `kkprl_private` dengan root terpisah dari disk lama dan `serve=false`; unduhan tetap melalui pemeriksaan session proposal atau permission Shield. Record lama pada disk `local` tetap dapat dibaca untuk kompatibilitas.

## Consequences

Positif:

- Word/PDF dapat diaudit melalui hash dan manifest yang sama.
- File asli tetap immutable dan privat.
- Export per bab tidak memproses lampiran bab lain.
- Implementasi tidak bergantung pada binary converter sistem yang belum tersedia.

Negatif:

- Fidelity terhadap template resmi perlu golden-file/manual QA lanjutan.
- PDF dengan konten visual kompleks perlu strategi render tambahan jika ekstraksi teks tidak cukup.
- Generator synchronous dipakai pada MVP dengan batas per bab; keputusan threshold dan migrasi queue produksi dicatat di ADR-013.

# ADR-016: Fail-Closed untuk Artefak Legacy KKPRL

- Status: Accepted
- Date: 2026-08-28
- Supersedes: bagian kompatibilitas disk lama pada ADR-009 untuk artefak proposal KKPRL

## Context

Requirement KKPRL mewajibkan lampiran dan dokumen proposal disimpan privat. Model baru menolak record pada disk selain
`kkprl_private`, tetapi row legacy atau data yang ditulis langsung ke database masih dapat menunjuk ke disk lain.
Membaca row tersebut saat snapshot, revisi, atau render dapat membuka jalur tidak terkontrol ke storage publik.

## Decision

Alur KKPRL fail-closed jika manifest atau attachment menunjuk disk selain `config('kkprl.storage_disk')` (default
`kkprl_private`). Snapshot, revisi, dan renderer menolak data tersebut sebelum membaca binary.

Record legacy tidak dipindahkan atau dihapus otomatis. Migrasi legacy harus memvalidasi ulang file, menyalin ke disk
privat dengan nama acak, memperbarui metadata melalui service resmi, lalu memverifikasi checksum dan aksesnya.

## Consequences

- Artefak legacy yang belum dimigrasikan tidak dapat diekspor atau disalin ke revisi.
- Tidak ada fallback ke `public` atau disk lain pada jalur proposal KKPRL.
- Proses migrasi membutuhkan runbook dan verifikasi operasional sebelum production release.
- Kompatibilitas disk lama pada fitur non-KKPRL tetap tidak berubah.

## Verification

- Model dan controller download menolak disk nonprivat.
- Snapshot dan revision copy menolak row legacy nonprivat sebelum binary dibaca.
- Regression test mencakup model guard dan legacy attachment handling.

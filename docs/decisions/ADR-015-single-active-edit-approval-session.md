# ADR-015 — Satu Approval dan Sesi Edit Aktif per Revisi

## Status

Accepted for MVP.

## Context

Petugas A dapat mengajukan edit darurat secara scoped. Tanpa pembatasan, satu revisi dapat memiliki beberapa approval `pending`/`approved` atau beberapa sesi edit aktif secara bersamaan. Dua jalur edit paralel membuat scope dan urutan audit sulit ditentukan.

## Decision

Untuk satu revisi:

- hanya satu approval berstatus `pending` atau `approved` boleh aktif;
- approval baru ditolak saat sesi edit masih aktif;
- `startSession()` menolak sesi aktif lain, termasuk pada data approval lama;
- approval `rejected`, `expired`, atau `used` tidak menghalangi permintaan baru setelah sesi selesai.

Guard dilakukan di dalam transaksi setelah row revisi dikunci. Approval tetap one-use dan session tetap scoped pada revisi, proposal, actor, dan scope yang disetujui.

## Consequences

- Konflik edit paralel ditolak lebih awal dengan error domain.
- Riwayat approval tetap append-only tanpa menghapus approval lama.
- Approval baru harus dibuat setelah approval sebelumnya selesai atau tidak aktif. Request baru menandai approval
  `approved` yang sudah melewati `expires_at` sebagai `expired` secara transaksional, sehingga tidak bergantung hanya
  pada scheduler.
- Database legacy yang sudah memiliki approval ganda tetap dilindungi saat sesi baru dimulai.

## Verification

Regression test memastikan permintaan approval aktif kedua ditolak, approval expired tidak menghalangi request baru,
dan self-approval/self-rejection tetap ditolak setelah row approval dikunci ulang.

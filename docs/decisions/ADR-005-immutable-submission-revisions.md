# ADR-005: Revisi sebagai Salinan dari Proposal Submitted

## Status

Accepted for MVP review workflow

## Date

2026-08-24

## Context

Proposal yang sudah dikirim harus konsisten dengan dokumen Word/PDF dan keadaan data saat submit. Petugas dapat menemukan kesalahan dan membutuhkan perbaikan, tetapi mengubah proposal awal akan merusak jejak review dan membuat output lama tidak dapat dipercaya.

## Decision

Proposal `submitted` tidak pernah ditimpa.

Ketika petugas meminta perbaikan:

1. Sistem mencatat instruksi `needs_revision`.
2. Sistem membuat salinan proposal baru yang terhubung ke proposal sebelumnya.
3. Salinan memiliki nomor revisi dan berstatus `draft`.
4. Pemohon mengubah salinan, bukan proposal awal.
5. Salinan yang selesai dikirim kembali menjadi `submitted` dan immutable.
6. Proposal awal, salinan, snapshot dokumen, lampiran, dan catatan review tetap dapat ditelusuri.

Default review tidak memberi petugas hak edit data pemohon. Untuk kondisi darurat atau keterpaksaan, permission khusus dapat mengubah salinan revisi melalui aksi yang meminta alasan dan mencatat before/after secara append-only. Proposal `submitted` asli tetap tidak dapat diubah.

Relasi revisi harus disimpan eksplisit melalui `root_proposal_id`, `revision_of_id`, `revision_number`, dan `revision_label`. Nomor tiket utama tetap sama untuk seluruh proposal family. Revisi hanya menambahkan label sederhana `rev-1`, `rev-2`, dan seterusnya.

Saat pemohon memasukkan nomor tiket utama + nomor HP, sistem mencari proposal family. Jika ada satu revisi aktif berstatus `draft`, revisi tersebut dibuka. Jika belum ada revisi aktif, sistem membuka proposal utama atau status terakhir. Nomor tiket utama tidak boleh menghasilkan error hanya karena proposal memiliki revisi.

Sistem hanya boleh memiliki satu revisi aktif berstatus `draft` dalam satu proposal family. Constraint atau service rule harus mencegah dua revisi aktif yang ambigu.

## Alternatives Considered

### Membuka kembali proposal awal

- Pro: implementasi lebih sederhana.
- Kontra: menghapus kepastian tentang isi yang sudah dikirim dan memutus jejak audit.
- Ditolak.

### Petugas mengedit langsung proposal submitted

- Pro: cepat untuk koreksi kecil.
- Kontra: pengguna tidak dapat membedakan data asli dan perubahan petugas; risiko perubahan tanpa persetujuan.
- Ditolak untuk MVP.

### Membuat proposal baru tanpa relasi

- Pro: model sederhana.
- Kontra: riwayat revisi dan hubungan dokumen hilang.
- Ditolak.

## Consequences

Positif:

- Proposal dan dokumen submitted tetap konsisten.
- Riwayat perubahan dapat diaudit.
- Petugas dan pemohon memiliki batas tanggung jawab jelas.

Negatif:

- Membutuhkan relasi revision dan tampilan riwayat.
- Data storage bertambah karena snapshot dan lampiran disalin atau direferensikan.
- Access flow revisi membutuhkan keputusan nomor tiket dan aturan session.

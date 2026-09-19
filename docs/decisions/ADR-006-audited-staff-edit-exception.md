# ADR-006: Pengecualian Edit Petugas dengan Audit Wajib

## Status

Accepted for revision workflow

## Date

2026-08-24

## Context

Alur normal revisi harus menjaga agar pemohon menjadi pihak yang memperbaiki data. Namun kondisi darurat atau keterpaksaan dapat membutuhkan bantuan langsung petugas.

Edit petugas tanpa batas dan tanpa audit akan mengaburkan siapa yang mengubah data serta merusak kepercayaan terhadap riwayat proposal.

## Decision

Default:

- Petugas hanya meninjau dan menulis catatan.
- Pemohon mengubah salinan revisi.

Exception dengan two-person approval:

- Petugas A meminta izin edit dengan target proposal/revisi dan alasan.
- Petugas B dengan permission approval khusus dari Filament Shield menyetujui atau menolak.
- Petugas A tidak dapat menyetujui permintaannya sendiri.
- Approval hanya berlaku pada proposal/revisi dan scope yang disetujui.
- Hanya permission khusus yang dapat mengedit salinan revisi setelah approval.
- Proposal `submitted` asli tidak dapat diedit.
- Alasan perubahan wajib diisi.
- Sistem menyimpan actor, waktu, request ID, field before, field after, dan alasan.
- Approval dan edit dicatat sebagai event terpisah.
- Riwayat review append-only.
- UI menandai perubahan sebagai **Diubah Petugas**.
- Generate dokumen berikutnya memakai data salinan revisi setelah perubahan tercatat.

Filament Shield yang sudah tersedia menjadi sumber kebenaran role dan permission. Petugas B tidak ditentukan melalui nama role yang hardcode. Role `pimpinan`, `pengelola`, `super-admin`, atau role lain dapat menjadi Petugas B jika memiliki permission approval. Permission meminta edit, menyetujui approval, dan menjalankan sesi edit harus dipisahkan. Walaupun super-admin memiliki bypass permission, Petugas A tetap tidak boleh menyetujui permintaannya sendiri.

Role dan permission actor pada saat request, approval, dan edit disimpan sebagai snapshot metadata audit agar konteks riwayat tetap jelas jika konfigurasi Shield berubah.

## Alternatives Considered

### Memberi semua petugas hak edit

- Pro: proses cepat.
- Kontra: risiko perubahan tidak terkontrol dan audit lemah.
- Ditolak.

### Petugas A menyetujui permintaannya sendiri

- Pro: alur lebih cepat.
- Kontra: menghilangkan pemisahan kewenangan dan meningkatkan risiko perubahan tanpa kontrol.
- Ditolak.

### Mengubah proposal submitted asli

- Pro: tidak perlu membuat salinan.
- Kontra: merusak immutable submission dan dokumen historis.
- Ditolak.

### Hanya menyimpan catatan teks

- Pro: sederhana.
- Kontra: tidak cukup untuk membuktikan nilai sebelum dan sesudah perubahan.
- Ditolak.

## Consequences

Positif:

- Bantuan darurat tetap tersedia.
- Riwayat tetap menjelaskan perubahan dan pelakunya.
- Permission edit dapat dibatasi dan direview terpisah.

Negatif:

- Membutuhkan event/audit schema dan UI riwayat.
- Membutuhkan approval request dengan scope, status, dan aturan expiry/one-time use.
- Petugas harus memahami kapan permission khusus boleh digunakan.
- Data before/after dapat sensitif dan harus dilindungi dengan permission review yang sesuai.

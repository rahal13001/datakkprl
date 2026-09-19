# ADR-007: Two-Person Approval untuk Edit Petugas

## Status

Accepted for revision workflow

## Date

2026-08-24

## Context

Petugas A dapat menghadapi kondisi darurat yang membutuhkan perubahan langsung pada salinan revisi. Namun edit langsung tanpa persetujuan petugas lain berisiko menghilangkan pemisahan kewenangan.

Kebutuhan bisnis menetapkan bahwa petugas A harus menunggu izin petugas B sebelum dapat mengedit.

## Decision

Gunakan alur two-person approval:

1. Petugas A membuat permintaan edit untuk proposal/revisi tertentu.
2. Permintaan memuat alasan, target field/section, dan konteks.
3. Petugas B dengan permission approval khusus dari Filament Shield meninjau permintaan.
4. Petugas B menyetujui atau menolak.
5. Petugas A hanya dapat memulai satu sesi edit jika approval aktif dan scope sesuai.
6. Dalam sesi aktif, A dapat melakukan beberapa perubahan yang tercakup scope.
7. Saat revisi dikirim, sesi selesai dan approval tidak dapat digunakan lagi.
8. Saat menyetujui, Petugas B menentukan waktu kedaluwarsa approval.
9. Sistem menutup sesi otomatis ketika waktu kedaluwarsa tercapai.
10. Approval yang sudah disetujui tetapi belum dipakai juga ditandai expired oleh sweeper ketika melewati expires_at.
11. Approval dan setiap edit dicatat sebagai event review terpisah.
12. Petugas A tidak boleh menyetujui permintaannya sendiri.
13. Proposal `submitted` asli tetap immutable.

Approval tidak memberi akses edit global. Approval harus dibatasi pada proposal/revisi, scope, petugas A, dan satu sesi edit.

Filament Shield yang sudah ada menjadi sumber kebenaran role dan permission. Petugas B tidak ditentukan melalui nama role yang hardcode. User dengan role `pimpinan`, `pengelola`, `super-admin`, atau role lain dapat menjadi Petugas B jika memiliki permission approval yang sesuai. Petugas A tetap tidak boleh menyetujui permintaannya sendiri, termasuk ketika konfigurasi super-admin memberi bypass permission umum. Permission meminta edit, menyetujui approval, dan menjalankan sesi edit harus dibedakan.

Role/permission actor saat request, approval, dan edit dicatat di audit metadata sebagai snapshot konteks; perubahan konfigurasi Shield di masa depan tidak boleh menghapus konteks riwayat lama.

## Alternatives Considered

### Semua petugas dapat edit

- Pro: paling cepat.
- Kontra: tidak ada separation of duties.
- Ditolak.

### Petugas A menyetujui sendiri

- Pro: tidak menunggu petugas lain.
- Kontra: tidak ada kontrol dua pihak.
- Ditolak.

### Petugas B langsung mengedit tanpa approval record

- Pro: lebih sedikit UI.
- Kontra: tidak membedakan permintaan, persetujuan, dan pelaksanaan.
- Ditolak.

## Consequences

Positif:

- Edit darurat tetap tersedia.
- Separation of duties lebih jelas.
- Audit dapat menunjukkan siapa meminta, siapa menyetujui, dan siapa mengubah.

Negatif:

- Membutuhkan tabel approval, permission baru, UI dua tahap, dan lifecycle sesi edit.
- Proses edit lebih lambat jika petugas B tidak tersedia.
- Aturan pembatalan sesi dan tampilan waktu kedaluwarsa harus ditetapkan sebelum implementasi.

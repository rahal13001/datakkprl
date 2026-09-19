# ADR-004: Proposal Builder sebagai Sistem Pendukung, Bukan Sistem Perizinan

## Status

Accepted

## Date

2026-08-24

## Context

Pembuat proposal membantu pengguna jasa dan pelaku usaha kecil menyiapkan dokumen berdasarkan template KKPRL. Setelah selesai, dokumen dikirim dengan status `submitted` agar petugas dapat meninjau.

Proses perizinan utama tetap berada pada e-Sea, OSS, atau kanal resmi lain. Proposal Builder tidak boleh memberi kesan bahwa status internal aplikasi adalah penerbitan, persetujuan, atau keputusan hukum.

## Decision

Batasi produk sebagai sistem pendukung:

- `draft` berarti dokumen masih dikerjakan.
- `submitted` berarti dokumen dikirim untuk review petugas.
- Status review internal, jika ditambahkan, hanya menggambarkan proses pendampingan.
- Aplikasi tidak menerbitkan izin.
- Aplikasi tidak menggantikan e-Sea atau OSS.
- Copywriting, label status, email, PDF, dan halaman petugas harus menyatakan batasan ini dengan jelas.
- Integrasi pengiriman ke e-Sea/OSS bukan bagian MVP.

## Alternatives Considered

### Menjadikan aplikasi sebagai sistem perizinan utama

- Pro: alur pengguna terlihat lebih lengkap.
- Kontra: membutuhkan otoritas, integrasi, validasi hukum, workflow resmi, dan tanggung jawab yang tidak termasuk ruang lingkup produk.
- Ditolak: bertentangan dengan tujuan sistem pendukung.

### Menggunakan status `approved`

- Pro: mudah dipahami secara internal.
- Kontra: berisiko disalahartikan sebagai izin resmi.
- Ditolak untuk default: gunakan istilah review internal yang jelas jika diperlukan.

## Consequences

Positif:

- Batas tanggung jawab produk jelas.
- Pengguna tidak mendapat ekspektasi bahwa download dokumen berarti izin terbit.
- Integrasi dengan e-Sea/OSS dapat dirancang kemudian tanpa mengubah fungsi inti draft.

Negatif:

- Pengguna tetap perlu mengirim atau mengajukan dokumen melalui kanal resmi.
- Copywriting dan status UI perlu ditinjau agar tidak menimbulkan klaim yang salah.

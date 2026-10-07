# ADR-001: Akses Publik Proposal dengan Nomor Tiket dan Nomor HP

## Status

Superseded pada kode tanggal 2026-10-07 oleh [ADR-024](ADR-024-akun-pemohon-terpisah.md), [ADR-025](ADR-025-approval-reset-akun-pemohon.md) dan [ADR-026](ADR-026-kepemilikan-dan-transisi-akses-pemohon.md). Isi keputusan lama dipertahankan sebagai sejarah; migration/deployment database pengguna belum dijalankan.

## Date

2026-08-24

## Context

Target pengguna adalah pengguna jasa dan pelaku usaha kecil yang perlu mengisi proposal dari HP tanpa proses pembuatan akun. Proposal panjang dan dapat dikerjakan bertahap, sehingga pengguna membutuhkan cara untuk kembali ke draft.

Aplikasi sudah memiliki alur publik dan data sensitif. Akses tidak boleh bergantung pada nomor tiket saja karena nomor tiket dapat diketahui atau dibagikan.

## Decision

Gunakan pasangan `nomor tiket + nomor HP` sebagai mekanisme resume proposal pada MVP.

Setelah pasangan diverifikasi:

- Sistem membuat session access terbatas.
- Nomor HP tidak dikirim ulang pada setiap langkah.
- Nomor HP disimpan mengikuti pola encryption/data protection aplikasi.
- Hash/HMAC nomor HP dipakai untuk exact lookup.
- Percobaan akses dibatasi dengan rate limit.
- Rate limit diterapkan pada pasangan credential dan alamat IP untuk mencegah enumeration lintas tiket/nomor HP.
- Error akses bersifat generik.
- File hanya diakses melalui endpoint terotorisasi dan private storage.

OTP tidak digunakan pada MVP. Boundary verifikasi harus dibuat cukup terisolasi agar OTP dapat ditambahkan tanpa mengubah domain proposal.

## Alternatives Considered

### Akun dan password penuh

- Pro: keamanan dan pemulihan lebih kuat.
- Kontra: menambah friksi bagi pengguna kecil dan bertentangan dengan tujuan formulir publik sederhana.
- Ditolak untuk MVP: terlalu berat untuk tahap awal.

### Nomor tiket saja

- Pro: sangat sederhana.
- Kontra: tidak cukup sebagai otorisasi dan mudah ditebak/dibagikan.
- Ditolak: risiko akses silang terlalu tinggi.

### OTP sejak versi pertama

- Pro: keamanan lebih kuat.
- Kontra: bergantung pada kanal SMS/WhatsApp dan menambah biaya serta kompleksitas operasional.
- Ditunda: dapat ditambahkan setelah alur dasar tervalidasi.

## Consequences

Positif:

- Tidak perlu tabel akun pemohon.
- Pengguna dapat melanjutkan draft dari HP lain.
- UX sederhana dan sesuai kebutuhan MVP.

Negatif dan mitigasi:

- Nomor HP bukan secret yang kuat; mitigasi rate limit, generic error, session timeout, HMAC lookup, dan private files wajib.
- Pemulihan ketika nomor tiket atau nomor HP hilang belum tersedia; perlu ditangani sebagai pertanyaan produk berikutnya.
- OTP masa depan harus ditambahkan sebagai lapisan verifikasi, bukan ditanam langsung di komponen form.

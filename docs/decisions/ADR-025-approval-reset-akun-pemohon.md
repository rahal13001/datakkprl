# ADR-025: Approval dan reset akun pemohon oleh petugas

## Status

Accepted; pengelolaan di panel Layanankkprl mendukung cutover akun bersama ADR-024/026.

## Date

2026-10-07

## Context

Approval akun berbeda dari approval edit darurat proposal. Reset harus memberi pemulihan tanpa mengambil alih akun petugas atau super_admin, dan tanpa layanan email/OTP/WhatsApp baru.

## Decision

Pengguna menetapkan role admin, pimpinan dan super_admin sebagai pengelola akun. Seeder khusus memberi permission ViewAny:KkprlApplicant, View:KkprlApplicant, Approve:KkprlApplicant, ResetPassword:KkprlApplicant dan AssignOwner:KkprlApplicant hanya pada ketiga role tersebut. Pemeriksaan memakai permission aktual, bukan nama role. Katalog tidak memakai ApproveEdit:KkprlProposal. Resource dikecualikan dari generator policy Shield untuk menjaga policy bisnis.

Navigation, daftar/detail, action dan service memeriksa permission. Melihat daftar tidak memberi hak melihat detail, approval, reset atau penetapan owner. Resource tidak mengizinkan CRUD akun petugas maupun penghapusan akun pemohon. Semua target action adalah KkprlApplicant; ID User tidak pernah dipakai sebagai target reset.

Pengguna memilih password baru dimasukkan petugas. Form meminta konfirmasi, minimal 12 karakter, dan penyampaian manual melalui kanal privat organisasi. Tidak ada pengiriman otomatis atau alur pemulihan mandiri. Password di-hash; remember token diganti dan session_version naik. Sesi lama gagal pada pemeriksaan akun berikutnya, termasuk operasi yang kembali dari layanan eksternal.

Audit KkprlApplicantEvent append-only mencatat actor, target, tindakan dan waktu. Tidak menyimpan password, hash password atau token. Alasan/referensi penetapan owner dienkripsi. Approval/revocation tidak mengubah status sistem lain.

## Alternatives Considered

Reset lewat email/OTP/WhatsApp tidak dipilih pengguna. Reset pada model User ditolak karena membuka risiko mengambil alih petugas. Menyembunyikan action saja ditolak; service wajib mengotorisasi ulang.

## Consequences

Permission seeder harus dijalankan secara eksplisit pada deployment yang kelak diotorisasi. Jangan memakai seeder keseluruhan sebagai pengganti. Kanal penyampaian manual mengikuti kebijakan organisasi; aplikasi tidak menambah persyaratan identitas.

Referensi: [Filament 5 resource authorization](https://filamentphp.com/docs/5.x/resources/overview).

## Verification

Tes administration dan Filament menguji pemisahan permission, penolakan request langsung, reset password/versi sesi, audit tanpa secret dan role yang disepakati. Hasil aktual dicatat pada laporan validasi.

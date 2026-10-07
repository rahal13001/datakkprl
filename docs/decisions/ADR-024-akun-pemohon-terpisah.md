# ADR-024: Akun dan guard pemohon terpisah

## Status

Accepted; bersama ADR-025/026 menggantikan ADR-001 pada kode setelah cutover boundary akun, builder dan unduhan. Deployment/database pengguna belum dijalankan.

## Date

2026-10-07

## Context

Pemohon memerlukan akun email/password dengan approval khusus KKPRL. User yang ada dipakai panel petugas, Spatie Permission dan Sanctum/mobile. Registrasi publik pada model tersebut berisiko bertabrakan dengan identitas petugas dan User.status yang memiliki arti lain.

## Decision

Gunakan KkprlApplicant pada tabel kkprl_applicants, provider Eloquent dan guard session kkprl_applicant. Registrasi hanya mengizinkan email dan password terkonfirmasi, menyimpan hash dan membuat akun pending tanpa draft. Akun tidak memiliki role, permission, trait API token atau broker reset mandiri. Approval menggunakan approved_at/approved_by, bukan User.status.

Login memeriksa kredensial dan mengikat session_version di bawah row lock akun. Setiap pemeriksaan akun mengambil versi fresh dari database. Reset menaikkan versi; guard cached atau sesi lama tidak bisa melewati pemeriksaan. Logout hanya mengeluarkan guard pemohon dan mengganti session ID, mempertahankan guard petugas dalam browser yang sama. Tidak ada remember-me bagi pemohon.

## Alternatives Considered

Model User/guard web bersama ditolak: batas petugas/API dan pemohon akan bergantung pada filtering di banyak sistem lain. Model/guard terpisah memberi pemisahan eksplisit tanpa mengubah sistem lain atau menambah layanan.

## Consequences

Tidak ada identitas tambahan yang diwajibkan. Nomor tiket tetap identitas proposal dan HP tetap kontak. Metadata akun/owner tidak dimasukkan ke snapshot; approval/reset tidak mengubah hash atau dokumen yang valid. Shared hosting cukup memakai session/database/hash Laravel yang tersedia.

Referensi API: [Laravel 13 authentication](https://laravel.com/docs/13.x/authentication), [keamanan Livewire 4](https://livewire.laravel.com/docs/4.x/security).

## Verification

KkprlApplicantIdentityTest: registrasi pending tanpa draft, input manipulatif, normalisasi email, guard terpisah, penolakan kredensial petugas dan versi sesi yang berubah. Hasil aktual ada di kkprl-applicant-account-validation.md.

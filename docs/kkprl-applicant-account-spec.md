# Akun pemohon dan approval KKPRL

Tanggal: 2026-10-07. Status: implementasi bertahap; bukan bukti siap produksi.

## Tujuan dan keputusan

Mengganti autentikasi tiket/HP dengan email dan password tanpa mengubah isi template proposal. Registrasi hanya membuat akun pending, tanpa draft atau AI. Nomor tiket tetap identitas internal; HP tetap data kontak proposal.

Versi aktual composer.lock: Laravel 13.26.1, Livewire 4.4.1, Filament 5.7.6, Spatie Permission 7.4.1, Shield dev-main.

Akun menggunakan model/tabel `KkprlApplicant` dan guard session `kkprl_applicant` terpisah dari `User`/`web`/Sanctum. Tidak ada trait role, token API, atau broker reset email pemohon. Approval menggunakan `approved_at`, terpisah dari `User.status`. Metadata akun dan owner tidak masuk identitas snapshot dokumen.

Role yang disepakati pengguna: `admin`, `pimpinan`, `super_admin` untuk daftar, approval, dan reset. Tetap gunakan permission aktual Spatie/Shield; seeder tidak memberi hak kepada role lain. Penetapan owner memakai permission khusus dan audit setelah petugas memverifikasi bukti secara manual. Tidak ada pencocokan otomatis email/HP/tiket. Pengguna memilih password baru dimasukkan petugas; penyampaian manual melalui kanal privat organisasi, tanpa integrasi pengiriman otomatis.

## Capability map dan urutan

| Modul | Tanggung jawab | Dependensi |
|---|---|---|
| applicant-identity | Model, registrasi/login/logout, versi sesi | — |
| applicant-administration | Permission, approval/revocation, reset, audit | applicant-identity |
| applicant-ownership | Owner proposal/revisi, penetapan legacy terverifikasi | applicant-identity, applicant-administration |
| applicant-access | Wadah dokumen, builder, penolakan akses lama | applicant-identity, applicant-ownership |

Urutan: skema/model dan identity; permission/admin; ownership; penggantian seluruh akses; regresi dan browser. Tidak ada deployment atau perubahan database pengguna selama pengembangan.

## Matriks akses

| Actor | Dokumen generated sendiri | Data/lampiran mentah | Builder/AI/GMRT/upload/generate/submit | Panel/API petugas | Admin akun |
|---|---|---|---|---|---|
| Anonim/sesi tiket lama | Tidak | Tidak | Tidak | Tidak | Tidak |
| Pemohon pending | Ya, setelah integrity check | Tidak | Tidak | Tidak | Tidak |
| Pemohon approved | Ya | Milik sendiri | Milik sendiri; lock/quota/revisi tetap berlaku | Tidak | Tidak |
| Petugas tanpa permission terkait | Sesuai permission download terpisah | Sesuai permission download terpisah | Hanya workflow edit scoped yang sudah ada | Sesuai aturan sistem lama | Tidak |
| Petugas berpermission akun | Tidak mendapat hak download tambahan | Tidak mendapat hak tambahan | Tidak mendapat hak tambahan | Sesuai aturan sistem lama | Sesuai permission khusus |

Permission akun: `ViewAny:KkprlApplicant`, `View:KkprlApplicant`, `Approve:KkprlApplicant`, `ResetPassword:KkprlApplicant`, `AssignOwner:KkprlApplicant`. Approval akun tidak memakai `ApproveEdit:KkprlProposal`.

## Batas keamanan

- Input registrasi hanya email, password, konfirmasi password. Tidak ada identitas tambahan.
- Setiap entry point memeriksa akun fresh, versi sesi, approval, dan owner; hidden button bukan kontrol akses.
- Pisahkan membaca dokumen generated dari akses builder. Pending memakai query dokumen dengan field allowlist, tanpa mengirim payload/chat/lampiran.
- Approval dicabut ketika operasi berjalan: periksa kembali sebelum persistence; transaksi persistence mengunci akun agar revocation/reset tidak berlomba dengan penulisan.
- Reset hanya menarget tabel pemohon. Password hash, remember token diganti, versi sesi dinaikkan. Semua sesi sebelumnya ditolak, termasuk request Livewire langsung. Logout pemohon tidak mengeluarkan petugas dari guard web.
- Audit append-only mencatat actor, target, waktu, tindakan dan referensi alasan/verifikasi; tidak pernah menyimpan password, hash password, atau token sesi.
- Snapshot/hash, encryption, private storage, lock, quota, approval edit petugas dan batas ilmiah tetap berlaku.

## Migrasi dan transisi

Tambah migration baru untuk tabel akun/audit dan FK owner nullable pada proposal. Row lama tetap utuh, owner null. Tidak menjalankan `migrate:fresh`, tidak mengubah migration lama, tidak menghapus proposal/chat/lampiran/dokumen/riwayat.

Petugas memilih akun dan root proposal setelah verifikasi manual, memasukkan alasan dan referensi bukti. Seluruh rantai revisi ditetapkan atomik; owner yang sudah berbeda ditolak, bukan ditimpa. Assignment tidak mengubah payload/status/snapshot atau updated_at proposal. Revisi berikutnya mewarisi owner dari root. Penetapan hanya metadata administratif melalui service terotorisasi; immutable payload tetap dijaga.

Pada cutover, endpoint tiket/HP menjadi pintu login akun; verifier lama tidak dapat menghasilkan grant. Sesi `kkprl_proposal_access` diabaikan. Tidak ada jalur klaim mandiri legacy. ADR-001 ditandai superseded setelah cutover benar-benar berlaku, bukan saat desain ini ditulis.

## Struktur dan konvensi

Model `app/Models`, service `app/Services`, komponen `app/Livewire`, resource panel `app/Filament/Layanankkprl/Resources`, migration baru `database/migrations`, tes `tests/Feature`, ADR `docs/decisions`. Gunakan konvensi PHP/Eloquent/Pint yang sudah ada, misalnya `abort_unless($authorized, 403)` pada boundary HTTP dan exception domain pada service.

## Acceptance criteria dan verifikasi

1. Registrasi valid pending, tidak membuat draft/AI; input role/approval/owner tidak berpengaruh.
2. Login/logout guard pemohon terpisah. Pemohon tidak dapat login panel maupun API petugas.
3. Pending melihat dokumen generated sendiri atau empty state, tanpa wizard/progress/chat/upload/generate. Seluruh tindakan builder/service/Livewire ditolak tanpa operasi eksternal atau perubahan data.
4. Approved memakai builder sendiri; lintas akun ditolak, termasuk pergantian ID dan session selection.
5. URL/sesi tiket/HP lama tidak menjadi bypass.
6. Navigation/detail/action/service admin memakai permission berbeda; petugas tanpa permission ditolak pada request langsung.
7. Reset hash bekerja; password dan sesi lama gagal; audit tidak membocorkan password. Password baru diisi petugas sesuai keputusan pengguna; penyampaian manual melalui kanal privat organisasi tanpa integrasi otomatis.
8. Assignment/revisi mempertahankan data, owner, history, dan validitas dokumen. Akun pending tidak mendapat lampiran mentah.
9. Approval/owner/versi sesi yang berubah saat AI/GMRT/render berjalan mencegah persistence hasil tanpa izin.
10. Private storage dan snapshot/hash tetap diuji. Regresi Bab 1–5, pasut/grafik/GMRT, Word/PDF dan workflow petugas lulus.
11. Browser desktop/mobile memverifikasi registrasi/login, pending empty state/dokumen, approved builder, admin; tidak ada overflow atau error console material.

Perintah: `rtk proxy php vendor/bin/phpunit --filter=KkprlApplicant`, tes KKPRL relevan dan `rtk proxy php vendor/bin/phpunit`; `rtk proxy vendor/bin/pint --test <file>`; `rtk npm run build`; `rtk git diff --check`. DB tes SQLite memory/fixture terisolasi, Gemini/GMRT fake/mock. Hasil aktual dicatat di laporan validasi; baseline historis bukan hasil perubahan ini. Tidak menambah dependency sistem, layanan baru, commit/push/deploy.

## Tahapan dan pertanyaan terbuka

- [x] Fondasi model/skema/registrasi/login dan tes manipulasi input.
- [x] Admin permission/approval/reset/audit; password dimasukkan petugas, penyampaian manual.
- [x] Owner legacy terverifikasi dan preservasi owner revisi.
- [x] Cutover akses, wadah dokumen pending, semua entry point/service dan checkpoint.
- [x] Regresi lengkap: 461 tests/2429 assertions; format/build, browser dan review kode.
- [x] Finalisasi refresh Graphify utama dari kode dan dokumentasi cutover.

Semua pertanyaan bisnis diajukan pada audit awal. Kepemilikan legacy sudah menerima usulan verifikasi manual, alasan, dan audit. Tidak ada daftar bukti identitas tambahan yang diwajibkan aplikasi.

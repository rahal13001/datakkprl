# ADR-026: Kepemilikan dan transisi akses pemohon

## Status

Accepted pada kode tanggal 2026-10-07, bersama ADR-024/025 menggantikan ADR-001. Migration/seeder/deployment database pengguna belum dijalankan.

## Context

Proposal lama tidak memiliki akun pemohon. Tiket, kontak atau kesamaan email tidak membuktikan kepemilikan. Dokumen generated perlu tetap dapat diunduh oleh pemohon pending, sementara builder dan lampiran mentah memerlukan approval.

## Decision

Migration baru menambah tabel akun/audit dan FK `kkprl_proposals.applicant_id` nullable. Data proposal, chat, dokumen, lampiran, review, encryption dan snapshot lama tidak diubah. Draft baru memperoleh owner dari guard backend; input owner/status/role tidak diterapkan.

Pengguna menyetujui penetapan legacy oleh petugas berpermission khusus setelah pemeriksaan bukti manual, alasan dan referensi verifikasi. Service mengunci root serta seluruh rantai revisi, menolak owner berbeda, lalu menetapkan family secara atomik dan menulis audit terenkripsi. Tidak mengubah payload/status/updated_at/snapshot. Revisi selanjutnya mewarisi owner root.

Dokumen generated sendiri diperiksa melalui `canReadDocuments`; approval tidak diperlukan. Wadah pending mengirim hanya identitas dokumen, bab, format dan waktu, tanpa payload/draft/chat/lampiran/progres. Builder memeriksa akun fresh, versi sesi, approval dan owner melalui access service serta hydration/action. Service draft, lampiran, submit dan ekspor memeriksa izin sebelum operasi dan dalam transaksi penulisan. AI/GMRT memeriksa approval sebelum request; komponen memeriksa owner/lock/checkpoint lagi sebelum persistence setelah respons. Render dokumen selesai sebelum checkpoint transaksi final; revocation/reset yang sudah berlaku mencegah penulisan generated maupun failed.

Upload sementara KKPRL menggunakan route signed khusus dengan applicant/proposal/session_version, validasi signature dan throttle Livewire yang tersedia. Controller mengunci proposal/akun dan memeriksa approval/owner/lock saat menerima file. Tidak mengubah endpoint upload modul lain. Tidak menambah dependency/service hosting.

Route start/resume lama mengarahkan ke login atau wadah akun. Verifier tiket/HP, resume service dan action komponen legacy fail-closed. Sesi `kkprl_proposal_access` tidak pernah memberi authority; selection baru hanya memilih proposal milik akun approved. Metadata akun/owner tetap di luar snapshot, sehingga perubahan approval/password/email akun tidak membuat dokumen invalid.

## Alternatives Considered

Claim otomatis berdasarkan email/HP/tiket ditolak: tidak ada bukti kepemilikan yang memadai. Memberi pending akses wizard read-only ditolak karena membuka data proposal dan fitur yang dilarang. Mengikat approval ke snapshot ditolak karena mengubah validitas dokumen tanpa perubahan isi.

## Migration and rollback

Backup DB dan private storage sebelum deployment yang kelak diotorisasi. Terapkan migration additive dan `KkprlApplicantPermissionSeeder` secara khusus; periksa hanya admin/pimpinan/super_admin mendapat permission. Jangan menjalankan `migrate:fresh` atau reset seeder umum. Legacy tanpa owner tetap disimpan dan tidak dapat diakses pemohon sampai assignment diaudit. Tidak ada migrasi otomatis yang mengambil alih data.

Rollback skema setelah ada akun/audit/assignment akan menghilangkan metadata akun; gunakan rencana pemulihan backup yang mempertahankan data baru, bukan menjalankan down di produksi secara spontan. Tes down/up hanya membentuk schema legacy pada SQLite terisolasi dan membuktikan up mempertahankan seluruh row lama serta file/snapshot.

## Verification

Tes akun/boundary/migration membuktikan pending denial, cross-account, versi sesi reset, legacy fail-closed, revocation selama AI/GMRT/PDF, private/integrity, preservasi row legacy dan owner revisi. Regresi serta browser desktop/mobile dicatat dengan hasil aktual pada [laporan akun](../kkprl-applicant-account-validation.md). Graf utama diperbarui dari kode dan dokumen; batas indeks/parser dicatat pada Graphify report.

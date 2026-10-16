# ADR-034: Penghapusan berkas hasil generate dengan snapshot tetap tersimpan

## Status

Accepted. Memperluas lifecycle artefak ADR-009/ADR-014; immutability snapshot dan arsip final ADR-005 tetap berlaku.

## Date

2026-10-12

## Context

Pemohon membuat beberapa versi Word/PDF selama menyusun draft. Versi lama sebelumnya disembunyikan oleh pemeriksaan snapshot, tetapi berkasnya tetap memakai storage. Model melarang delete agar snapshot dan jejak review tidak hilang. Pengguna meminta penghapusan berkas yang tidak dipakai dan menetapkan bahwa arsip submitted/needs_revision tetap disimpan.

## Decision

- Tambahkan nullable `deleted_at` dan `deleted_by_applicant_id` melalui migration baru. Snapshot/manifest terenkripsi, hash, owner proposal dan storage pointer tetap immutable; record tidak dihapus dan tidak memakai global scope SoftDeletes.
- `KkprlProposalDocumentDeletionService` hanya menerima ID. Pemohon harus approved dengan sesi valid, memiliki proposal, dan proposal belum locked. Query awal scoped owner; transaksi mengunci proposal, memeriksa ulang akun/owner, lalu mengunci dokumen. Guard web atau permission download petugas tidak memberi aksi hapus pemohon.
- Path diturunkan dari root/revisi/chapter/hash/format pada disk private lokal. Nama legacy tanpa manifest hash juga dikenali. Resolved path harus tepat di bawah root private, tanpa alias symlink, traversal, direktori atau referensi bersama dengan record lain. Tidak menghapus direktori atau lampiran.
- Tombstone dan event `document_deleted` append-only ditulis sebelum unlink dalam transaksi. Audit mencatat actor pemohon, target, chapter/format/hash dan waktu, tanpa kredensial atau isi binary. Kegagalan storage menggagalkan transaksi; missing file dapat dibereskan dan request berulang idempotent.
- Portal approved dan wizard memperlihatkan metadata aman hasil generate aktif, termasuk versi lama/missing dengan unduhan dinonaktifkan. Tanggal dan referensi proposal membedakan versi. Pending hanya memperoleh dokumen valid sendiri dan tidak memperoleh tombol/aksi hapus.
- Tombstone ditolak pada download pemohon maupun petugas. Panel petugas tetap read-only dan menampilkan waktu berkas dihapus.
- Generate eksplisit setelah penghapusan memakai unique identity existing dan membersihkan marker berkas setelah checkpoint berhasil. Riwayat penghapusan tetap ada. Marker audit penghapusan proposal dibandingkan dari awal batch hingga setiap checkpoint; penghapusan saat render format pertama berlangsung menggagalkan batch sebelum format kedua dapat menghidupkan berkas kembali. Marker/account metadata tidak masuk hash snapshot.

## Alternatives Considered

Hard delete record ditolak karena menghilangkan snapshot dan riwayat. Soft delete tanpa unlink ditolak karena tidak mengurangi storage. Hapus pada pending atau arsip final ditolak sesuai batas akses dan keputusan pengguna. Queue/outbox/scheduler tidak ditambahkan karena kebutuhan ini dapat dipenuhi sinkron di shared hosting.

## Consequences

Berkas yang tidak dipakai benar-benar dilepas, sementara snapshot tersedia untuk audit. Word dan PDF dikelola masing-masing. Draft lengkap yang terbuka dapat dibuat ulang; arsip final mengikuti revisi.

Database dan filesystem tidak mempunyai commit atomik bersama. Unlink gagal membatalkan metadata/audit. Commit DB gagal setelah unlink dapat menyisakan record aktif dengan file hilang; download fail-closed dan penghapusan dapat diulang untuk menyelesaikan marker. Salinan yang diunduh atau backup hosting tidak termasuk target penghapusan. Tidak ada cleanup otomatis atau migrasi data pengguna.

## Verification

[Spesifikasi](../kkprl-document-deletion-spec.md) memuat acceptance criteria dan rencana migrasi. [Laporan validasi](../kkprl-document-deletion-validation.md) mencatat hasil aktual serta batas browser/hosting.

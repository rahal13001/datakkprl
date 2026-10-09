# Perbaikan skema institusi proposal KKPRL

Tanggal: 9 Oktober 2026. Status: spesifikasi perbaikan insiden hosting.

## Bukti dan batas diagnosis

Pengguna melaporkan UPDATE autosave gagal dengan MySQL 1054 pada `institution_name`. Pemeriksaan baca saja menunjukkan migration `2026_08_24_000002_add_institution_protection_to_kkprl_proposals` tercatat pada batch 23, tetapi `institution_name` dan `institution_name_encrypted` tidak ditemukan pada tabel proposal database aplikasi. Riwayat migration dan skema aktual tidak konsisten. Penyebab historis ketidaksesuaian belum diketahui; tidak ada akses langsung ke hosting.

Setter model menulis NULL ke kolom legacy dan nilai terenkripsi ke kolom encrypted. Kedua kolom tetap dibutuhkan, termasuk saat input institusi kosong. Migration lama yang tercatat selesai tidak dijalankan kembali oleh Artisan.

## Perubahan dan migrasi

Tambahkan migration baru `2026_10_09_000001_repair_kkprl_proposal_institution_columns.php`. Periksa keberadaan setiap kolom secara terpisah; tambahkan hanya kolom yang hilang sebagai TEXT nullable. Jangan mengubah migration lama, mencabut catatan batch 23, menjalankan backfill, atau mengubah data pengguna. Tabel proposal harus sudah ada; kesalahan koneksi/skema tidak disembunyikan dengan melewati migration.

Migration berjalan pada skema utuh maupun skema parsial. Dijalankan oleh satu operator pada satu deployment; pemeriksaan kolom bukan penguncian terhadap dua operator yang melakukan DDL bersamaan. MySQL DDL tidak diasumsikan transactional. Jika operasi terputus sesudah kolom pertama ditambahkan, eksekusi berikutnya melewati kolom tersebut.

`down()` mempertahankan kolom karena migration tidak mengetahui kolom mana yang sudah ada sebelum perbaikan. Rollback kode tidak menghapus data institusi yang mungkin sudah disimpan setelah perbaikan. Pemulihan skema/data melalui backup adalah tindakan operasional terpisah.

## Matriks akses dan dampak

| Pelaku | Perubahan akses |
|---|---|
| Pemohon pending | Tidak ada; dokumen milik sendiri sesuai aturan existing |
| Pemohon approved | Autosave dapat berjalan kembali setelah skema diperbaiki, tetap tunduk pada ownership, lock, quota dan validasi |
| Petugas | Permission approval akun/edit/reset tidak berubah |
| Operator deployment | Menjalankan migration melalui akses deployment existing; tidak ada endpoint publik untuk perbaikan |

Tidak ada perubahan route, form, progres wajib Bab 1, approval, API, sistem lain, dependency atau layanan shared hosting. Nilai hilang yang tidak pernah tersimpan tidak dapat dipulihkan oleh penambahan kolom. Payload terenkripsi yang sudah ada tetap dipertahankan.

## Acceptance criteria

1. Reproduksi autosave sebelum perbaikan gagal pada skema tanpa kedua kolom.
2. Skema tanpa kedua kolom, tanpa salah satu kolom, dan skema utuh semuanya dapat menerima migration.
3. Eksekusi ulang tidak menghapus atau menimpa nilai yang sudah ada.
4. Artisan menjadwalkan migration baru meskipun migration lama tercatat selesai.
5. Row proposal/revisi, owner, payload terenkripsi, chat, lampiran, dokumen, audit dan snapshot/hash tidak berubah akibat migration.
6. Sesudah perbaikan, autosave institusi bekerja dengan plaintext NULL dan nilai encrypted dapat dibaca melalui model.
7. `down()` tidak menghapus kolom atau data.
8. Pengujian memakai SQLite terisolasi; format dan suite regresi dijalankan. Pengujian MySQL hosting tetap diverifikasi operator setelah deployment.

Lihat [ADR-029](decisions/ADR-029-additive-institution-schema-repair.md) dan [validasi/runbook](kkprl-institution-schema-repair-validation.md).
